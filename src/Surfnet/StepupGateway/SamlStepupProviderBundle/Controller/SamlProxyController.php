<?php

/**
 * Copyright 2015 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Controller;

use DateTime;
use Exception;
use SAML2\Constants;
use SAML2\Response as SAMLResponse;
use SAML2\XML\saml\Issuer;
use Surfnet\SamlBundle\Http\XMLResponse;
use Surfnet\SamlBundle\Metadata\MetadataFactory;
use Surfnet\StepupGateway\GatewayBundle\Container\ContainerController;
use Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController;
use Surfnet\StepupGateway\GatewayBundle\Exception\ResponseFailureException;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidSubjectException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\NotConnectedServiceProviderException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\SecondfactorVerificationRequiredException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ProviderRepository;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\ProxyResponseFactory;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway\ConsumeAssertionService;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway\LoginService;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway\SecondFactorVerificationService;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ResponseHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handling of GSSP registration and verification.
 *
 * See docs/GatewayState.md for a high-level diagram on how this controller
 * interacts with outside actors and other parts of Stepup.
 *
 * Should be refactored, {@see https://www.pivotaltracker.com/story/show/90169776}
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class SamlProxyController extends ContainerController
{
    /**
     * Proxy a GSSP authentication request to the remote GSSP SSO endpoint.
     *
     * The user is about to be sent to the remote GSSP application for
     * registration. Verification is not initiated with a SAML AUthnRequest,
     * see sendSecondFactorVerificationAuthnRequestAction().
     *
     * The service provider in this context is SelfService (when registering
     * a token) or RA (when vetting a token).
     */
    #[Route(
        path: '/gssp/{provider}/single-sign-on',
        name: 'gssp_verify',
        methods: ['GET']
    )]
    public function singleSignOn(
        string  $provider,
        Request $httpRequest,
    ): RedirectResponse {
        $provider = $this->getProvider($provider);

        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');
        $gsspLoginService = $this->getGsspLoginService();

        $logger = $this->get('logger');
        $logger->notice('Received AuthnRequest, started processing');

        try {
            $proxyRequest = $gsspLoginService->singleSignOn($provider, $httpRequest);
        } catch (NotConnectedServiceProviderException) {
            throw new AccessDeniedHttpException();
        }

        return $redirectBinding->createResponseFor($proxyRequest);
    }

    /**
     * Start a GSSP single sign-on.
     *
     * The user has selected a second factor token and the token happens to be
     * a GSSP token. The SecondFactorController therefor did an internal
     * redirect (see SecondFactorController::verifyGssfAction) to this method.
     *
     * In this method, an authn request is created. This authn request is sent
     * directly to the remote GSSP SSO URL, and the response is handled in
     * consumeAssertionAction().
     */
    public function sendSecondFactorVerificationAuthnRequest(
        string $provider,
        string $subjectNameId,
        string $responseContextServiceId,
        string $relayState,
    ): RedirectResponse {

        $provider = $this->getProvider($provider);
        $gsspSecondFactorVerificationService = $this->getGsspSecondFactorVerificationService();
        $authnRequest = $gsspSecondFactorVerificationService->sendSecondFactorVerificationAuthnRequest(
            $provider,
            $subjectNameId,
            $responseContextServiceId,
        );
        $provider->getStateHandler()->setRelayState($relayState);

        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');

        return $redirectBinding->createResponseFor($authnRequest);
    }

    /**
     * Process an assertion received from the remote GSSP application.
     *
     * The GSSP application sent an assertion back to the gateway. When
     * successful, the user is sent back to:
     *
     *  1. in case of registration: back to the originating SP (SelfService or RA)
     *  2. in case of verification: internal redirect to SecondFactorController
     *
     * @throws Exception
     */
    #[Route(
        path: '/gssp/{provider}/consume-assertion',
        name: 'gssp_consume_assertion',
        methods: ['POST']
    )]
    public function consumeAssertion(string $provider, Request $httpRequest): Response
    {
        $provider = $this->getProvider($provider);

        $consumeAssertionService = $this->getGsspConsumeAssertionService();
        $proxyResponseFactory = $this->getProxyResponseFactory($provider);

        try {
            $response = $consumeAssertionService->consumeAssertion($provider, $httpRequest, $proxyResponseFactory);
        } catch (ResponseFailureException $e) {
            $response = $this->createResponseFailureResponse(
                $provider,
                $this->getDestination($provider->getStateHandler()),
                $this->getIssuer($provider->getStateHandler()),
                $e->getMessage(),
            );
            return $this->renderSamlResponse('consume_assertion', $provider->getStateHandler(), $response);
        } catch (InvalidSubjectException) {
            return $this->renderSamlResponse(
                'recoverable_error',
                $provider->getStateHandler(),
                $this->createAuthnFailedResponse(
                    $provider,
                    $this->getDestination($provider->getStateHandler()),
                ),
            );
        } catch (SecondfactorVerificationRequiredException) {
            // The provider state handler has no access to the session object,
            // hence we use the proxy state handler
            $stateHandler = $this->get('gateway.proxy.sso.state_handler');

            return $this->forward(
                'Surfnet\StepupGateway\GatewayBundle\Controller\SecondFactorController::gssfVerified',
                [
                    // The authentication mode is loaded from session, based on the request id
                    'authenticationMode' => $stateHandler->getAuthenticationModeForRequestId(
                        $consumeAssertionService->getReceivedRequestId(),
                    ),
                ],
            );
        } catch (Exception $e) {
            throw $e;
        }

        return $this->renderSamlResponse('consume_assertion', $provider->getStateHandler(), $response);
    }


    #[Route(
        path: '/gssp/{provider}/metadata',
        name: 'gssp_saml_metadata',
        methods: ['GET']
    )]
    public function metadata(string $provider): XMLResponse
    {
        $provider = $this->getProvider($provider);

        /** @var MetadataFactory $factory */
        $factory = $this->get('gssp.provider.' . $provider->getName() . '.metadata.factory');

        return new XMLResponse($factory->generate());
    }

    private function getProvider(string $provider): Provider
    {
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->get('gssp.provider_repository');

        if (!$providerRepository->has($provider)) {
            throw new NotFoundHttpException(
                sprintf('Requested GSSP "%s" does not exist or is not registered', $provider),
            );
        }

        return $providerRepository->get($provider);
    }

    private function getDestination(StateHandler $stateHandler): string
    {
        if ($stateHandler->secondFactorVerificationRequested()) {
            // This can either be an SFO or 'regular' SSO authentication.
            // Both use a ResponseContext service of their own
            $responseContextServiceId = $stateHandler->getResponseContextServiceId();
            // GSSP verification action, return to SP from GatewayController state!
            $destination = $this->get($responseContextServiceId)->getDestination();
        } else {
            // GSSP registration action, return to SP remembered in ssoAction().
            $serviceProvider = $this->getServiceProvider(
                $stateHandler->getRequestServiceProvider(),
            );

            $destination = $serviceProvider->determineAcsLocation(
                $stateHandler->getRequestAssertionConsumerServiceUrl(),
                $this->get('logger'),
            );
        }

        return $destination;
    }

    private function getIssuer(StateHandler $stateHandler): Issuer
    {
        // This can either be a SFO or 'regular' SSO authentication. Both use a ResponseContext service of their own
        $responseContextServiceId = $stateHandler->getResponseContextServiceId();
        if (!$responseContextServiceId) {
            throw new RuntimeException(
                sprintf(
                    'Unable to find the ResponseContext service-id for this authentication or registration, ' .
                    'service-id provided was: "%s"',
                    $responseContextServiceId,
                ),
            );
        }
        // GSSP verification action, return to SP from GatewayController state!
        /** @var ResponseContext $responseService */
        $responseService = $this->get($responseContextServiceId);
        return $responseService->getIssuer();
    }

    public function renderSamlResponse(string $view, StateHandler $stateHandler, SAMLResponse $response): Response
    {
        /** @var ResponseHelper $responseHelper */
        $responseHelper = $this->get('second_factor_only.adfs.response_helper');
        $logger = $this->get('logger');
        $logger->notice(sprintf('Rendering SAML Response with view "%s"', $view));

        $parameters = [
            'acu' => $response->getDestination(),
            'response' => $this->getResponseAsXML($response),
            'relayState' => $stateHandler->getRelayState(),
        ];
        $responseContext = $this->getResponseContext('gateway.proxy.sfo.state_handler');

        // Test if we should add ADFS response parameters
        $inResponseTo = $responseContext->getInResponseTo();
        $isAdfsResponse = $responseHelper->isAdfsResponse($inResponseTo);
        $logger->notice(sprintf('Responding to "%s" an ADFS response? %s', $inResponseTo, $isAdfsResponse ? 'yes' : 'no'));
        if ($isAdfsResponse) {
            $adfsParameters = $responseHelper->retrieveAdfsParameters();
            $logMessage = 'Responding with additional ADFS parameters, in response to request: "%s", with view: "%s"';
            if (!$response->isSuccess()) {
                $logMessage = 'Responding with an AuthnFailed SamlResponse with ADFS parameters, in response to AR: "%s", with view: "%s"';
            }
            $logger->notice(sprintf($logMessage, $inResponseTo, $view));
            $parameters['adfs'] = $adfsParameters;
            $parameters['acu'] = $responseContext->getDestinationForAdfs();
        }

        $response = parent::render(
            '@default/saml_proxy/' . $view . '.html.twig',
            $parameters,
        );

        // clear the state so we can call again :)
        $stateHandler->clear();

        return $response;
    }

    /**
     * @return string
     */
    private function getResponseAsXML(SAMLResponse $response): string
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }

    /**
     * Response that indicates that an error occurred in the responder
     * (the gateway). Used to indicate that we could not process the
     * response we received from the upstream GSSP
     *
     * The correct Destination (where did the SAMLResponse originate from.
     * And the Issuer (who issued the response) are explicitly set on the response
     * allowing for correctly setting them.
     */
    private function createResponseFailureResponse(
        Provider $provider,
        string $destination,
        Issuer $issuer,
        string $message,
    ): SAMLResponse {
        $response = $this->createResponse($provider, $destination);
        // Overwrite the issuer with the correct issuer for the saml failed response
        $response->setIssuer($issuer);
        $response->setStatus([
            'Code' => Constants::STATUS_RESPONDER,
            'SubCode' => Constants::STATUS_AUTHN_FAILED,
            'Message' => $message
        ]);

        return $response;
    }

    /**
     * Response that indicates that the authentication could not be performed correctly. In this context it means
     * that the upstream GSSP did not responsd with the same NameID as we request to authenticate in the AuthnRequest
     */
    private function createAuthnFailedResponse(Provider $provider, ?string $destination): SAMLResponse
    {
        $response = $this->createResponse($provider, $destination);
        $response->setStatus(
            [
                'Code' => Constants::STATUS_RESPONDER,
                'SubCode' => Constants::STATUS_AUTHN_FAILED,
            ],
        );

        return $response;
    }

    /**
     * Creates a standard response with default status Code (success)
     */
    private function createResponse(Provider $provider, ?string $destination): SAMLResponse
    {
        $context = $this->getResponseContext();
        $response = new SAMLResponse();
        $response->setDestination($destination);
        $response->setIssuer($context->getIssuer());
        $response->setIssueInstant((new DateTime('now'))->getTimestamp());
        $response->setInResponseTo($provider->getStateHandler()->getRequestId());

        return $response;
    }
    /**
     * @return LoginService
     */
    private function getGsspLoginService()
    {
        return $this->get('gssp.service.gssp.login');
    }

    /**
     * @return SecondFactorVerificationService
     */
    private function getGsspSecondFactorVerificationService()
    {
        return $this->get('gssp.service.gssp.second_factor_verification');
    }

    /**
     * @return ConsumeAssertionService
     */
    private function getGsspConsumeAssertionService()
    {
        return $this->get('gssp.service.gssp.consume_assertion');
    }

    /**
     * @param Provider $provider
     * @return ProxyResponseFactory
     */
    private function getProxyResponseFactory(Provider $provider)
    {
        return $this->get('gssp.provider.' . $provider->getName() . '.response_proxy');
    }

    /**
     * @return \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext
     */
    public function getResponseContext($mode = 'gateway.proxy.sso.state_handler')
    {
        $stateHandler = $this->get($mode);

        $responseContextServiceId = $stateHandler->getResponseContextServiceId();

        if (!$responseContextServiceId) {
            return $this->get(GatewayController::RESPONSE_CONTEXT_SERVICE_ID);
        }

        return $this->get($responseContextServiceId);
    }

    private function getServiceProvider(?string $serviceProvider)
    {
        $connectedServiceProviders = $this->get('gssp.connected_service_providers');
        return $connectedServiceProviders->getConfigurationOf($serviceProvider);
    }
}
