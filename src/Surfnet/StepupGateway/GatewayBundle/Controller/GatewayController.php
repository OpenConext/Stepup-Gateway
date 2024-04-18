<?php

/**
 * Copyright 2014 SURFnet bv.
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

use Psr\Log\LoggerInterface;
use SAML2\Response as SAMLResponse;
use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\GatewayBundle\Exception\RequesterFailureException;
use Surfnet\StepupGateway\GatewayBundle\Exception\ResponseFailureException;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\ConsumeAssertionService;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\FailedResponseService;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\LoginService;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\RespondService;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\CookieService;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\CookieServiceInterface;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ResponseHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Entry point for the Stepup login flow.
 *
 * See docs/GatewayState.md for a high-level diagram on how this controller
 * interacts with outside actors and other parts of Stepup.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GatewayController extends AbstractController
{
    public const RESPONSE_CONTEXT_SERVICE_ID = 'gateway.proxy.response_context';
    public const MODE_SFO = 'sfo';
    public const MODE_SSO = 'sso';

    public function __construct(
        private readonly LoggerInterface         $logger,
        private readonly RedirectBinding         $redirectBinding,
        private readonly LoginService            $gatewayLoginService,
        private readonly FailedResponseService   $gatewayFailedResponseService,
        private readonly ConsumeAssertionService $consumeAssertionService,
        private readonly ProxyStateHandler       $ssoProxyStateHandler,
        private readonly ProxyStateHandler       $sfoProxyStateHandler,
        private readonly RespondService         $gatewayRespondService,
        private readonly RequestStack        $requestStack,
        private readonly CookieServiceInterface $ssoCookieService,
        private readonly ResponseHelper $responseHelper,
        private readonly ResponseContext $gatewayProxyResponseContext,
        private readonly ResponseContext $sfoContext,
    ) {
    }

    /**
     * Receive an AuthnRequest from a service provider.
     *
     * The service provider is either a Stepup component (SelfService, RA) or
     * an external service provider.
     *
     * This single sign-on action will start a new SAML request to the remote
     * IDP configured in Stepup (most likely to be an instance of OpenConext
     * EngineBlock).
     */
    #[Route(
        path: '/authentication/single-sign-on',
        name: 'gateway_identityprovider_sso',
        methods: ['GET', 'POST']
    )]
    public function sso(Request $httpRequest): Response
    {
        $this->logger->notice('Received AuthnRequest, started processing');

        try {
            $proxyRequest = $this->gatewayLoginService->singleSignOn($httpRequest);
        } catch (RequesterFailureException) {
            $response = $this->gatewayFailedResponseService->createRequesterFailureResponse(
                $this->getResponseContext(self::MODE_SSO),
            );

            return $this->renderSamlResponse('consume_assertion', $response, $httpRequest, self::MODE_SSO);
        }

        return $this->redirectBinding->createResponseFor($proxyRequest);
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

        try {
            $this->consumeAssertionService->consumeAssertion($request, $responseContext);
        } catch (ResponseFailureException) {
            $response = $this->gatewayFailedResponseService->createResponseFailureResponse($responseContext);

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

        $response = $this->gatewayRespondService->respond($responseContext);
        $this->gatewayRespondService->resetRespondState($responseContext);

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

        $response = $this->gatewayFailedResponseService->sendLoaCannotBeGiven($responseContext);

        return $this->renderSamlResponse('consume_assertion', $response, $request, $authenticationMode);
    }

    public function sendAuthenticationCancelledByUser(): Response
    {
        // The authentication mode is read from the parent request, in the meantime a forward was followed, making
        // reading the auth mode from the current request impossible.
        // @see: \Surfnet\StepupGateway\GatewayBundle\Controller\SecondFactorController::cancelAuthenticationAction
        $request = $this->requestStack->getParentRequest();
        if (!$request->get('authenticationMode', false)) {
            throw new RuntimeException('Unable to determine the authentication mode in the sendAuthenticationCancelledByUser action');
        }
        $authenticationMode = $request->get('authenticationMode');

        $this->supportsAuthenticationMode($authenticationMode);
        $responseContext = $this->getResponseContext($authenticationMode);

        $response = $this->gatewayFailedResponseService->sendAuthenticationCancelledByUser($responseContext);

        return $this->renderSamlResponse('consume_assertion', $response, $request, $authenticationMode);
    }

    public function renderSamlResponse(
        string $view,
        SAMLResponse $response,
        Request $request,
        string $authenticationMode,
    ): Response {

        $this->supportsAuthenticationMode($authenticationMode);
        $responseContext = $this->getResponseContext($authenticationMode);

        $parameters = [
            'acu' => $responseContext->getDestination(),
            'response' => $this->getResponseAsXML($response),
            'relayState' => $responseContext->getRelayState(),
        ];

        // Test if we should add ADFS response parameters
        $inResponseTo = $responseContext->getInResponseTo();
        if ($this->responseHelper->isAdfsResponse($inResponseTo)) {
            $adfsParameters = $this->responseHelper->retrieveAdfsParameters();
            $logMessage = 'Responding with additional ADFS parameters, in response to request: "%s", with view: "%s"';
            if (!$response->isSuccess()) {
                $logMessage = 'Responding with an AuthnFailed SamlResponse with ADFS parameters, in response to AR: "%s", with view: "%s"';
            }
            $this->logger->notice(sprintf($logMessage, $inResponseTo, $view));
            $parameters['adfs'] = $adfsParameters;
            $parameters['acu'] = $responseContext->getDestinationForAdfs();
        }

        $httpResponse = $this->render($view, $parameters);

        if ($response->isSuccess()) {
            $this->ssoCookieService->handleSsoOn2faCookieStorage($responseContext, $request, $httpResponse);
        }

        return $httpResponse;
    }

    public function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        return parent::render(
            '@default/gateway/'.$view.'.html.twig',
            $parameters,
            $response,
        );
    }

    // TODO:
    // This resolves to a service. Then the service is used to get the ID.
    // Then the ID is used to get the ResponseContext.
    // This is a bit convoluted. Can we simplify this?
    public function getResponseContext($authenticationMode): ResponseContext
    {
        $sfoResponseContextServiceId = $this->sfoProxyStateHandler->getResponseContextServiceId();
        $ssoResponseContextServiceId = $this->ssoProxyStateHandler->getResponseContextServiceId();

        return match ($authenticationMode) {
            self::MODE_SFO => $this->sfoContext,
            self::MODE_SSO => $this->gatewayProxyResponseContext,
            default => throw new RuntimeException('Invalid authentication mode requested'),
        };
    }

    private function getResponseAsXML(SAMLResponse $response): string
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }


    private function supportsAuthenticationMode($authenticationMode): void
    {
        if (self::MODE_SSO !== $authenticationMode && self::MODE_SFO !== $authenticationMode) {
            throw new InvalidArgumentException('Invalid authentication mode requested');
        }
    }
}
