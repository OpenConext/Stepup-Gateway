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
use Surfnet\StepupGateway\GatewayBundle\Exception\RequesterFailureException;
use Surfnet\StepupGateway\GatewayBundle\Exception\ResponseFailureException;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\ConsumeAssertionService;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\FailedResponseService;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\LoginService;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\RespondService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Entry point for the Stepup login flow.
 *
 * See docs/GatewayState.md for a high-level diagram on how this controller
 * interacts with outside actors and other parts of Stepup.
 */
class GatewayController extends Controller
{
    const RESPONSE_CONTEXT_SERVICE_ID = 'gateway.proxy.response_context';

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
     * @param Request $httpRequest
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function ssoAction(Request $httpRequest)
    {
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->get('logger');

        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');
        $gatewayLoginService = $this->getGatewayLoginService();

        $logger->notice('Received AuthnRequest, started processing');

        try {
            $proxyRequest = $gatewayLoginService->singleSignOn($httpRequest);
        } catch (RequesterFailureException $e) {
            $response = $this->getGatewayFailedResponseService()->createRequesterFailureResponse($this->getResponseContext());

            return $this->renderSamlResponse('consumeAssertion', $response);
        }

        return $redirectBinding->createResponseFor($proxyRequest);
    }

    /**
     *
     */
    public function proxySsoAction()
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
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function consumeAssertionAction(Request $request)
    {
        $responseContext = $this->getResponseContext();
        $gatewayLoginService = $this->getGatewayConsumeAssertionService();

        try {
            $gatewayLoginService->consumeAssertion($request, $responseContext);
        } catch (ResponseFailureException $e) {
            $response = $this->getGatewayFailedResponseService()->createResponseFailureResponse($responseContext);

            return $this->renderSamlResponse('unprocessableResponse', $response);
        }

        return $this->forward('SurfnetStepupGatewayGatewayBundle:SecondFactor:selectSecondFactorForVerification');
    }

    /**
     * Send a SAML response back to the service provider.
     *
     * Second factor verification handled by SecondFactorController is
     * finished. The user was forwarded back to this action with an internal
     * redirect. This method sends a AuthnResponse back to the service
     * provider in response to the AuthnRequest received in ssoAction().
     */
    public function respondAction()
    {
        $responseContext = $this->getResponseContext();
        $gatewayLoginService = $this->getGatewayRespondService();

        $response = $gatewayLoginService->respond($responseContext);
        $gatewayLoginService->resetRespondState($responseContext);

        return $this->renderSamlResponse('consumeAssertion', $response);
    }

    /**
     * @return Response
     */
    public function sendLoaCannotBeGivenAction()
    {
        $responseContext = $this->getResponseContext();
        $gatewayLoginService = $this->getGatewayFailedResponseService();

        $response = $gatewayLoginService->sendLoaCannotBeGiven($responseContext);

        return $this->renderSamlResponse('consumeAssertion', $response);
    }

    /**
     * @return Response
     */
    public function sendAuthenticationCancelledByUserAction()
    {
        $responseContext = $this->getResponseContext();
        $gatewayLoginService = $this->getGatewayFailedResponseService();

        $response = $gatewayLoginService->sendAuthenticationCancelledByUser($responseContext);

        return $this->renderSamlResponse('consumeAssertion', $response);
    }

    /**
     * @param string         $view
     * @param SAMLResponse $response
     * @return Response
     */
    public function renderSamlResponse($view, SAMLResponse $response)
    {
        $responseContext = $this->getResponseContext();

        return $this->render($view, [
            'acu'        => $responseContext->getDestination(),
            'response'   => $this->getResponseAsXML($response),
            'relayState' => $responseContext->getRelayState()
        ]);
    }

    /**
     * @param string   $view
     * @param array    $parameters
     * @param Response $response
     * @return Response
     */
    public function render($view, array $parameters = array(), Response $response = null): Response
    {
        return parent::render(
            'SurfnetStepupGatewayGatewayBundle:Gateway:' . $view . '.html.twig',
            $parameters,
            $response
        );
    }

    /**
     * @return \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext
     */
    public function getResponseContext()
    {
        $stateHandler = $this->get('gateway.proxy.state_handler');

        $responseContextServiceId = $stateHandler->getResponseContextServiceId();

        if (!$responseContextServiceId) {
            return $this->get(static::RESPONSE_CONTEXT_SERVICE_ID);
        }

        return $this->get($responseContextServiceId);
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
}
