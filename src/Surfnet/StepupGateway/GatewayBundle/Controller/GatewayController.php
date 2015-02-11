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

use Exception;
use SAML2_Assertion;
use SAML2_Const;
use SAML2_Response;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GatewayController extends Controller
{
    public function ssoAction(Request $httpRequest)
    {
        /** @var \Monolog\Logger $logger */
        $logger = $this->get('logger');
        $logger->notice('Received AuthnRequest, started processing');

        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');

        try {
            $originalRequest = $redirectBinding->processRequest($httpRequest);
        } catch (Exception $e) {
            $logger->critical(sprintf('Could not process Request, error: "%s"', $e->getMessage()));

            return $this->render('unrecoverableError');
        }

        $logger->notice(sprintf(
            'AuthnRequest processing complete, received AuthnRequest from "%s", request ID: "%s"',
            $originalRequest->getServiceProvider(),
            $originalRequest->getRequestId()
        ));

        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler $stateHandler */
        $stateHandler = $this->get('gateway.proxy.state_handler');
        $stateHandler
            ->generateSessionIndex($originalRequest->getServiceProvider())
            ->setRequestId($originalRequest->getRequestId())
            ->setRequestServiceProvider($originalRequest->getServiceProvider())
            ->setRelayState($httpRequest->get(AuthnRequest::PARAMETER_RELAY_STATE, ''));

        // check if the requested loa is supported
        $requiredLoa = $originalRequest->getAuthenticationContextClassRef();
        if ($requiredLoa && !$this->get('gateway.service.loa_resolution')->hasLoa($requiredLoa)) {
            $logger->info(sprintf(
                'Requested required LOA "%s" does not exist, sending response with status Requester Error',
                $requiredLoa
            ));

            $response = $this->createRequesterFailureResponse();
            $this->renderSamlResponse('consumeAssertion', $response);
        }

        $stateHandler->setRequestAuthnContextClassRef($originalRequest->getAuthenticationContextClassRef());

        $proxyRequest = AuthnRequestFactory::createNewRequest(
            $this->get('surfnet_saml.hosted.service_provider'),
            $this->get('surfnet_saml.remote.idp')
        );

        $proxyRequest->setScoping([$originalRequest->getServiceProvider()]);
        $stateHandler->setGatewayRequestId($proxyRequest->getRequestId());

        $this->get('logger')->notice(sprintf(
            'Sending Proxy AuthnRequest with request ID: "%s" for original AuthnRequest "%s"',
            $proxyRequest->getRequestId(),
            $originalRequest->getRequestId()
        ));

        return $redirectBinding->createRedirectResponseFor($proxyRequest);
    }

    public function proxySsoAction()
    {
        throw new HttpException(418, 'Not Yet Implemented');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function consumeAssertionAction(Request $request)
    {
        $this->get('logger')->notice('Received SAMLResponse, attempting to process for Proxy Response');

        $responseContext = $this->getResponseContext();
        try {
            /** @var \SAML2_Assertion $assertion */
            $assertion = $this->get('surfnet_saml.http.post_binding')->processResponse(
                $request,
                $this->get('surfnet_saml.remote.idp'),
                $this->get('surfnet_saml.hosted.service_provider')
            );
        } catch (Exception $exception) {
            /** @var \Monolog\Logger $logger */
            $logger = $this->get('logger');
            $logger->error(sprintf('Could not process received Response, error: "%s"', $exception->getMessage()));

            $response = $this->createResponseFailureResponse($responseContext);

            return $this->renderSamlResponse('unprocessableResponse', $response);
        }

        $adaptedAssertion = new AssertionAdapter($assertion);
        $expectedResponse = $responseContext->getExpectedInResponseTo();
        if (!$adaptedAssertion->inResponseToMatches($expectedResponse)) {
            $this->get('logger')->critical(sprintf(
                'Received Response with unexpected InResponseTo: "%s", %s',
                $adaptedAssertion->getInResponseTo(),
                ($expectedResponse ? 'expected "' . $expectedResponse . '"' : ' no response expected')
            ));

            return $this->render('unrecoverableError');
        }

        $responseContext->saveAssertion($assertion);

        $requiredLoa = $responseContext->getRequiredLoa();
        if (!$requiredLoa) {
            return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:respond');
        }

        /** @var \Surfnet\StepupGateway\GatewayBundle\Service\StepUpAuthenticationService $stepupService */
        $stepupService = $this->get('gateway.service.stepup_authentication');
        if ($stepupService->isIntrinsicLoa($requiredLoa)) {
            return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:respond');
        }

        return $this->forward('SurfnetStepupGatewayGatewayBundle:SecondFactor:selectSecondFactorForVerification');
    }

    public function respondAction()
    {
        $this->get('logger')->notice('Creating Response');

        $responseContext = $this->getResponseContext();

        /** @var \Surfnet\StepupGateway\GatewayBundle\Service\ProxyResponseService $proxyResponseService */
        $proxyResponseService = $this->get('gateway.service.response_proxy');
        $response             = $proxyResponseService->createProxyResponse(
            $responseContext->reconstituteAssertion(),
            $responseContext->getServiceProvider(),
            $responseContext->isSecondFactorVerified() ? $responseContext->getRequiredLoa() : null
        );

        $responseContext->responseSent();

        $this->get('logger')->notice(sprintf(
            'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
            $responseContext->getInResponseTo(),
            $response->getId()
        ));

        return $this->renderSamlResponse('consumeAssertion', $response);
    }

    public function sendLoaCannotBeGivenAction()
    {
        $this->get('logger')->notice('LOA cannot be given, creating Response with NoAuthnContext status');

        $responseContext = $this->getResponseContext();

        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('gateway.proxy.response_builder');

        $response = $responseBuilder
            ->createNewResponse($responseContext)
            ->setResponseStatus(SAML2_Const::STATUS_NO_AUTHN_CONTEXT)
            ->get();

        $this->get('logger')->notice(sprintf(
            'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
            $responseContext->getInResponseTo(),
            $response->getId()
        ));

        return $this->renderSamlResponse('consumeAssertion', $response);
    }

    /**
     * @param string         $view
     * @param SAML2_Response $response
     * @return Response
     */
    public function renderSamlResponse($view, SAML2_Response $response)
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
    public function render($view, array $parameters = array(), Response $response = null)
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
        return $this->get('gateway.proxy.response_context');
    }

    /**
     * @param SAML2_Response $response
     * @return string
     */
    private function getResponseAsXML(SAML2_Response $response)
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }

    /**
     * @return SAML2_Response
     */
    private function createRequesterFailureResponse()
    {
        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('gateway.proxy.response_builder');
        $context = $this->getResponseContext();

        $response = $responseBuilder
            ->createNewResponse($context)
            ->setResponseStatus(SAML2_Const::STATUS_REQUESTER, SAML2_Const::STATUS_REQUEST_UNSUPPORTED)
            ->get();

        return $response;

    }

    /**
     * @param $context
     * @return SAML2_Response
     */
    private function createResponseFailureResponse($context)
    {
        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('gateway.proxy.response_builder');

        $response = $responseBuilder
            ->createNewResponse($context)
            ->setResponseStatus(SAML2_Const::STATUS_RESPONDER)
            ->get();

        return $response;
    }
}
