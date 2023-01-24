<?php

/**
 * Copyright 2014 SURFnet bv
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
use Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController;
use Surfnet\StepupGateway\GatewayBundle\Exception\ResponseFailureException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidSubjectException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\NotConnectedServiceProviderException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\SecondfactorVerificationRequiredException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\ProxyResponseFactory;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway\ConsumeAssertionService;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway\LoginService;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway\SecondFactorVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
class SamlProxyController extends Controller
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
     *
     * @param string $provider
     * @param Request $httpRequest
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function singleSignOnAction($provider, Request $httpRequest)
    {
        $provider = $this->getProvider($provider);

        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');
        $gsspLoginService = $this->getGsspLoginService();

        $logger = $this->get('logger');
        $logger->notice('Received AuthnRequest, started processing');

        try {
            $proxyRequest = $gsspLoginService->singleSignOn($provider, $httpRequest);
        } catch (NotConnectedServiceProviderException $e) {
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
     *
     * @param string $provider
     * @param string $subjectNameId
     * @param string $responseContextServiceId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendSecondFactorVerificationAuthnRequestAction($provider, $subjectNameId, $responseContextServiceId)
    {
        $provider = $this->getProvider($provider);

        $gsspSecondFactorVerificationService = $this->getGsspSecondFactorVerificationService();

        $authnRequest = $gsspSecondFactorVerificationService->sendSecondFactorVerificationAuthnRequest(
            $provider,
            $subjectNameId,
            $responseContextServiceId
        );

        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
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
     * @param string $provider
     * @param Request $httpRequest
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws Exception
     */
    public function consumeAssertionAction($provider, Request $httpRequest)
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
                $e->getMessage()
            );
            return $this->renderSamlResponse('consume_assertion', $provider->getStateHandler(), $response);
        } catch (InvalidSubjectException $e) {
            return $this->renderSamlResponse(
                'recoverable_error',
                $provider->getStateHandler(),
                $this->createAuthnFailedResponse(
                    $provider,
                    $this->getDestination($provider->getStateHandler())
                )
            );
        } catch (SecondfactorVerificationRequiredException $e) {
            // The provider state handler has no access to the session object, hence we use the proxy state handler
            $stateHandler = $this->get('gateway.proxy.sso.state_handler');
            return $this->forward(
                'SurfnetStepupGatewayGatewayBundle:SecondFactor:gssfVerified',
                [
                    // The authentication mode is loaded from session, based on the request id
                    'authenticationMode' => $stateHandler->getAuthenticationModeForRequestId(
                        $consumeAssertionService->getReceivedRequestId()
                    ),
                ]
            );
        } catch (Exception $e) {
            throw $e;
        }

        return $this->renderSamlResponse('consume_assertion', $provider->getStateHandler(), $response);
    }

    /**
     * @param string $provider
     * @return XMLResponse
     */
    public function metadataAction($provider)
    {
        $provider = $this->getProvider($provider);

        /** @var \Surfnet\SamlBundle\Metadata\MetadataFactory $factory */
        $factory = $this->get('gssp.provider.' . $provider->getName() . '.metadata.factory');

        return new XMLResponse($factory->generate());
    }

    /**
     * @param string $provider
     * @return \Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider
     */
    private function getProvider($provider)
    {
        /** @var \Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ProviderRepository $providerRepository */
        $providerRepository = $this->get('gssp.provider_repository');

        if (!$providerRepository->has($provider)) {
            throw new NotFoundHttpException(
                sprintf('Requested GSSP "%s" does not exist or is not registered', $provider)
            );
        }

        return $providerRepository->get($provider);
    }

    /**
     * @param StateHandler $stateHandler
     * @return string
     */
    private function getDestination(StateHandler $stateHandler)
    {
        if ($stateHandler->secondFactorVerificationRequested()) {
            // This can either be a SFO or 'regular' SSO authentication. Both use a ResponseContext service of their own
            $responseContextServiceId = $stateHandler->getResponseContextServiceId();
            // GSSP verification action, return to SP from GatewayController state!
            $destination = $this->get($responseContextServiceId)->getDestination();
        } else {
            // GSSP registration action, return to SP remembered in ssoAction().
            $serviceProvider = $this->getServiceProvider(
                $stateHandler->getRequestServiceProvider()
            );

            $destination = $serviceProvider->determineAcsLocation(
                $stateHandler->getRequestAssertionConsumerServiceUrl(),
                $this->get('logger')
            );
        }

        return $destination;
    }

    /**
     * @param string $view
     * @param StateHandler $stateHandler
     * @param SAMLResponse $response
     * @return Response
     */
    public function renderSamlResponse($view, StateHandler $stateHandler, SAMLResponse $response)
    {
        $parameters = [
            'acu' => $response->getDestination(),
            'response' => $this->getResponseAsXML($response),
            'relayState' => $stateHandler->getRelayState(),
        ];

        $response = parent::render(
            'SurfnetStepupGatewaySamlStepupProviderBundle:saml_proxy:' . $view . '.html.twig',
            $parameters
        );

        // clear the state so we can call again :)
        $stateHandler->clear();

        return $response;
    }

    /**
     * @param SAMLResponse $response
     * @return string
     */
    private function getResponseAsXML(SAMLResponse $response)
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }

    /**
     * Response that indicates that an error occurred in the responder (the gateway). Used to indicate that we could
     * not process the response we received from the upstream GSSP
     *
     * @param Provider $provider
     * @param string $destination
     * @return SAMLResponse
     */
    private function createResponseFailureResponse(Provider $provider, $destination, $message)
    {
        $response = $this->createResponse($provider, $destination);
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
     *
     * @param Provider $provider
     * @param string $destination
     * @return SAMLResponse
     */
    private function createAuthnFailedResponse(Provider $provider, $destination)
    {
        $response = $this->createResponse($provider, $destination);
        $response->setStatus(
            [
                'Code' => Constants::STATUS_RESPONDER,
                'SubCode' => Constants::STATUS_AUTHN_FAILED,
            ]
        );

        return $response;
    }

    /**
     * Creates a standard response with default status Code (success)
     *
     * @param Provider $provider
     * @param string $destination
     * @return SAMLResponse
     */
    private function createResponse(Provider $provider, $destination)
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
     * @param string $serviceProvider
     * @return \Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider
     */
    private function getServiceProvider($serviceProvider)
    {
        /**
         * @var \Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ConnectedServiceProviders $connectedServiceProviders
         */
        $connectedServiceProviders = $this->get('gssp.connected_service_providers');
        return $connectedServiceProviders->getConfigurationOf($serviceProvider);
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
    public function getResponseContext()
    {
        $stateHandler = $this->get('gateway.proxy.sso.state_handler');

        $responseContextServiceId = $stateHandler->getResponseContextServiceId();

        if (!$responseContextServiceId) {
            return $this->get(GatewayController::RESPONSE_CONTEXT_SERVICE_ID);
        }

        return $this->get($responseContextServiceId);
    }
}
