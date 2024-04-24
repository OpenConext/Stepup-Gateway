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

namespace Surfnet\StepupGateway\GatewayBundle\Controller;

use SAML2\Response as SAMLResponse;
use Surfnet\StepupGateway\GatewayBundle\Container\ContainerController;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\GatewayBundle\Exception\RequesterFailureException;
use Surfnet\StepupGateway\GatewayBundle\Exception\ResponseFailureException;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\ConsumeAssertionService;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\FailedResponseService;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\LoginService;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\RespondService;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ResponseHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Entry point for the Stepup login flow.
 *
 * See docs/GatewayState.md for a high-level diagram on how this controller
 * interacts with outside actors and other parts of Stepup.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GatewayController extends ContainerController
{
    public const RESPONSE_CONTEXT_SERVICE_ID = 'gateway.proxy.response_context';
    public const MODE_SFO = 'sfo';
    public const MODE_SSO = 'sso';

    /**
     * Receive an AuthnRequest from a service provider.
     *
     * The service provider is either a Stepup component (SelfService, RA) or
     * an external service provider.
     *
     * This single sign-on action will start a new SAML request to the remote
     * IDP configured in Stepup (most likely to be an instance of OpenConext
     * EngineBlock).
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    #[Route(
        path: '/authentication/single-sign-on',
        name: 'gateway_identityprovider_sso',
        methods: ['GET', 'POST']
    )]
    public function sso(Request $httpRequest)
    {
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->get('logger');

        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');
        $gatewayLoginService = $this->getGatewayLoginService();

        $logger->notice('Received AuthnRequest, started processing');

        try {
            $proxyRequest = $gatewayLoginService->singleSignOn($httpRequest);
        } catch (RequesterFailureException) {
            $response = $this->getGatewayFailedResponseService()->createRequesterFailureResponse(
                $this->getResponseContext(self::MODE_SSO),
            );

            return $this->renderSamlResponse('consume_assertion', $response, $httpRequest, self::MODE_SSO);
        }

        return $redirectBinding->createResponseFor($proxyRequest);
    }

    #[Route(
        path: '/authentication/single-sign-on/{idpKey}',
        name: 'gateway_identityprovider_sso_proxy',
        methods: ['POST']
    )]
    public function proxySso(): never
    {
        throw new HttpException(418, 'Not Yet Implemented');
    }

    /**
     * Receive an AuthnResponse from an identity provider.
     *
     * The AuthnRequest started in ssoAction() resulted in an AuthnResponse
     * from the IDP. This method handles the assertion and forwards the user
     * using an internal redirect to the SecondFactorController to start the
     * actual second factor verification.
     */
    #[Route(
        path: '/authentication/consume-assertion',
        name: 'gateway_serviceprovider_consume_assertion',
        methods: ['POST']
    )]
    public function consumeAssertion(Request $request): Response
    {
        $responseContext = $this->getResponseContext(self::MODE_SSO);
        $gatewayLoginService = $this->getGatewayConsumeAssertionService();

        try {
            $gatewayLoginService->consumeAssertion($request, $responseContext);
        } catch (ResponseFailureException) {
            $response = $this->getGatewayFailedResponseService()->createResponseFailureResponse($responseContext);

            return $this->renderSamlResponse('unprocessable_response', $response, $request, self::MODE_SSO);
        }

        // Forward to the selectSecondFactorForVerificationSsoAction, this in turn will forward to the correct
        // verification action (based on authentication type sso/sfo)
        return $this->forward('Surfnet\StepupGateway\GatewayBundle\Controller\SecondFactorController::selectSecondFactorForVerificationSso');
    }

    /**
     * Send a SAML response back to the service provider.
     *
     * Second factor verification handled by SecondFactorController is
     * finished. The user was forwarded back to this action with an internal
     * redirect. This method sends a AuthnResponse back to the service
     * provider in response to the AuthnRequest received in ssoAction().
     */
    public function respond(Request $request): Response
    {
        $responseContext = $this->getResponseContext(self::MODE_SSO);
        $gatewayLoginService = $this->getGatewayRespondService();

        $response = $gatewayLoginService->respond($responseContext);
        $gatewayLoginService->resetRespondState($responseContext);

        return $this->renderSamlResponse('consume_assertion', $response, $request, self::MODE_SSO);
    }

    /**
     * This action is also used from the context of SecondFactorOnly authentications.
     */
    public function sendLoaCannotBeGiven(Request $request): Response
    {
        if (!$request->get('authenticationMode', false)) {
            throw new RuntimeException('Unable to determine the authentication mode in the sendLoaCannotBeGiven action');
        }
        $authenticationMode = $request->get('authenticationMode');
        $this->supportsAuthenticationMode($authenticationMode);
        $responseContext = $this->getResponseContext($authenticationMode);
        $gatewayLoginService = $this->getGatewayFailedResponseService();

        $response = $gatewayLoginService->sendLoaCannotBeGiven($responseContext);

        return $this->renderSamlResponse('consume_assertion', $response, $request, $authenticationMode);
    }

    public function sendAuthenticationCancelledByUser(): Response
    {
        // The authentication mode is read from the parent request, in the meantime a forward was followed, making
        // reading the auth mode from the current request impossible.
        // @see: \Surfnet\StepupGateway\GatewayBundle\Controller\SecondFactorController::cancelAuthenticationAction
        $requestStack = $this->get('request_stack');
        $request = $requestStack->getParentRequest();
        if (!$request->get('authenticationMode', false)) {
            throw new RuntimeException('Unable to determine the authentication mode in the sendAuthenticationCancelledByUser action');
        }
        $authenticationMode = $request->get('authenticationMode');

        $this->supportsAuthenticationMode($authenticationMode);
        $responseContext = $this->getResponseContext($authenticationMode);
        $gatewayLoginService = $this->getGatewayFailedResponseService();

        $response = $gatewayLoginService->sendAuthenticationCancelledByUser($responseContext);

        return $this->renderSamlResponse('consume_assertion', $response, $request, $authenticationMode);
    }

    public function renderSamlResponse(
        string $view,
        SAMLResponse $response,
        Request $request,
        string $authenticationMode,
    ): Response {
        $logger = $this->get('logger');
        /** @var ResponseHelper $responseHelper */
        $responseHelper = $this->get('second_factor_only.adfs.response_helper');

        $this->supportsAuthenticationMode($authenticationMode);
        $responseContext = $this->getResponseContext($authenticationMode);

        $parameters = [
            'acu' => $responseContext->getDestination(),
            'response' => $this->getResponseAsXML($response),
            'relayState' => $responseContext->getRelayState(),
        ];

        // Test if we should add ADFS response parameters
        $inResponseTo = $responseContext->getInResponseTo();
        if ($responseHelper->isAdfsResponse($inResponseTo)) {
            $adfsParameters = $responseHelper->retrieveAdfsParameters();
            $logMessage = 'Responding with additional ADFS parameters, in response to request: "%s", with view: "%s"';
            if (!$response->isSuccess()) {
                $logMessage = 'Responding with an AuthnFailed SamlResponse with ADFS parameters, in response to AR: "%s", with view: "%s"';
            }
            $logger->notice(sprintf($logMessage, $inResponseTo, $view));
            $parameters['adfs'] = $adfsParameters;
            $parameters['acu'] = $responseContext->getDestinationForAdfs();
        }

        $httpResponse = $this->render($view, $parameters);

        if ($response->isSuccess()) {
            $ssoCookieService = $this->get('gateway.service.sso_2fa_cookie');
            $ssoCookieService->handleSsoOn2faCookieStorage($responseContext, $request, $httpResponse);
        }

        return $httpResponse;
    }

    /**
     * @param string $view
     */
    public function render($view, array $parameters = [], ?Response $response = null): Response
    {
        return parent::render(
            '@default/gateway/'.$view.'.html.twig',
            $parameters,
            $response,
        );
    }

    public function getResponseContext($authenticationMode): ResponseContext
    {
        return match ($authenticationMode) {
            self::MODE_SFO => $this->get($this->get('gateway.proxy.sfo.state_handler')->getResponseContextServiceId()),
            self::MODE_SSO => $this->get($this->get('gateway.proxy.sso.state_handler')->getResponseContextServiceId()),
            default => throw new RuntimeException('Invalid authentication mode requested'),
        };
    }

    private function getResponseAsXML(SAMLResponse $response): string
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }

    /**
     * @return LoginService
     */
    private function getGatewayLoginService()
    {
        return $this->get('gateway.service.gateway.login');
    }

    /**
     * @return ConsumeAssertionService
     */
    private function getGatewayConsumeAssertionService()
    {
        return $this->get('gateway.service.gateway.consume_assertion');
    }

    /**
     * @return RespondService
     */
    private function getGatewayRespondService()
    {
        return $this->get('gateway.service.gateway.respond');
    }

    /**
     * @return FailedResponseService
     */
    private function getGatewayFailedResponseService()
    {
        return $this->get('gateway.service.gateway.failed_response');
    }

    private function supportsAuthenticationMode($authenticationMode): void
    {
        if (self::MODE_SSO !== $authenticationMode && self::MODE_SFO !== $authenticationMode) {
            throw new InvalidArgumentException('Invalid authentication mode requested');
        }
    }
}
