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
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionAdapter;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GatewayController extends Controller
{
    public function ssoAction(Request $httpRequest)
    {
        $this->get('logger')->info('Received AuthnRequest, started processing');

        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');

        try {
            $originalRequest = $redirectBinding->processRequest($httpRequest);
        } catch (Exception $e) {
            return $this->render('unprocessableRequest');
        }

        $this->get('logger')->info(sprintf(
            'AuthnRequest processing complete, received AuthnRequest from "%s", request ID: "%s"',
            $originalRequest->getServiceProvider(),
            $originalRequest->getRequestId()
        ));

        $proxyRequest = AuthnRequestFactory::createNewRequest(
            $this->get('surfnet_saml.hosted.service_provider'),
            $this->get('surfnet_saml.remote.idp')
        );

        $proxyRequest->setScoping([$originalRequest->getServiceProvider()]);

        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler $stateHandler */
        $stateHandler = $this->get('gateway.proxy.state_handler');
        $stateHandler
            ->generateSessionIndex($originalRequest->getServiceProvider())
            ->setRequestId($originalRequest->getRequestId())
            ->setRequestServiceProvider($originalRequest->getServiceProvider())
            ->setRelayState($httpRequest->get(AuthnRequest::PARAMETER_RELAY_STATE, ''))
            ->setRequestAuthnContextClassRef($originalRequest->getRequestedAuthenticationContext())
            ->setGatewayRequestId($proxyRequest->getRequestId());

        $this->get('logger')->info(sprintf(
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
        $this->get('logger')->info('Received SAMLResponse, attempting to process for Proxy Response');

        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler $stateHandler */
        $stateHandler = $this->get('gateway.proxy.state_handler');
        /** @var \Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService $samlEntityRepository */
        $samlEntityRepository = $this->get('saml.entity_repository');
        $serviceProvider = $samlEntityRepository->getServiceProvider($stateHandler->getRequestServiceProvider());
        $context = new ResponseContext(
            $this->get('surfnet_saml.hosted.identity_provider'),
            $serviceProvider,
            $stateHandler
        );

        try {
            /** @var \SAMl2_Assertion $assertion */
            $assertion = $this->get('surfnet_saml.http.post_binding')->processResponse(
                $request,
                $this->get('surfnet_saml.remote.idp'),
                $this->get('surfnet_saml.hosted.service_provider')
            );
        } catch (Exception $exception) {
            /** @var \Monolog\Logger $logger */
            $logger = $this->get('logger');
            $logger->error('Could not process received Response, error: "%s"', $exception->getMessage());

            $response = $this->createResponseFailureResponse($context);

            return $this->render('unprocessableResponse', [
                'acs' => $serviceProvider->getAssertionConsumerUrl(),
                'response' => $this->getResponseAsXML($response),
                'relayState' => $stateHandler->getRelayState()
            ]);
        }

        $adaptedAssertion = new AssertionAdapter($assertion);
        if (!$adaptedAssertion->inResponseToMatches($stateHandler->getGatewayRequestId())) {

            // @todo logging, error out. End of the line here.

            return $this->render('mismatchedInResponseTo');
        }

        //@todo here we do the LOA detection and do the actual LOA checking

        // @todo serialize Assertion to XML and store in state

        // @todo move to respond action
        /** @var \Surfnet\StepupGateway\GatewayBundle\Service\ProxyResponseService $proxyResponseService */
        $proxyResponseService = $this->get('gateway.service.response_proxy');
        $response = $proxyResponseService->createProxyResponse($assertion, $serviceProvider);

        return $this->render(
            'consumeAssertion',
            [
                'acu' => $serviceProvider->getAssertionConsumerUrl(),
                'response' => base64_encode($response->toUnsignedXML()->ownerDocument->saveXML()),
                'relayState' => $stateHandler->getRelayState()
            ]
        );
    }

    public function render($view, array $parameters = array(), Response $response = null)
    {
        return parent::render(
            'SurfnetStepupGatewayGatewayBundle:Gateway:' . $view . '.html.twig',
            $parameters,
            $response
        );
    }

    private function getResponseAsXML(\SAML2_Response $response)
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }

    /**
     * @param $context
     * @return \SAML2_Response
     */
    public function createResponseFailureResponse($context)
    {
        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('gateway.proxy.response_builder');

        $response = $responseBuilder
            ->createNewResponse($context)
            ->setResponseStatus(\SAML2_Const::STATUS_RESPONDER)
            ->get();

        return $response;
    }
}
