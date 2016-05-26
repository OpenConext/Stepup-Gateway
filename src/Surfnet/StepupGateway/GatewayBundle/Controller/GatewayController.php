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
use Psr\Log\LoggerInterface;
use SAML2_Assertion;
use SAML2_Const;
use SAML2_Response;
use Surfnet\SamlBundle\Http\XMLResponse;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Surfnet\StepupBundle\Value\AuthnContextClass;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionAdapter;
use Surfnet\StepupGateway\GatewayBundle\Service\ProxyResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GatewayController extends Controller
{
    const RESPONSE_CONTEXT_SERVICE_ID = 'gateway.proxy.response_context';

    public function metadataAction()
    {
        return new XMLResponse(
          $this->get('surfnet_saml.metadata_factory')->generate()
        );
    }

    public function ssoAction(Request $httpRequest)
    {
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->get('logger');
        $logger->notice('Received AuthnRequest, started processing');

        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');

        try {
            $originalRequest = $redirectBinding->processRequest($httpRequest);
        } catch (Exception $e) {
            $logger->critical(sprintf('Could not process Request, error: "%s"', $e->getMessage()));

            return $this->render(
              'SurfnetStepupGatewayGatewayBundle:Gateway:unrecoverableError.html.twig'
            );
        }

        $originalRequestId = $originalRequest->getRequestId();
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice(sprintf(
            'AuthnRequest processing complete, received AuthnRequest from "%s", request ID: "%s"',
            $originalRequest->getServiceProvider(),
            $originalRequest->getRequestId()
        ));

        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler $stateHandler */
        $stateHandler = $this->get('gateway.proxy.state_handler');
        $stateHandler
            ->setRequestId($originalRequestId)
            ->setRequestServiceProvider($originalRequest->getServiceProvider())
            ->setRelayState($httpRequest->get(AuthnRequest::PARAMETER_RELAY_STATE, ''))
            ->setResponseAction('SurfnetStepupGatewayGatewayBundle:Gateway:respond')
            ->setResponseContextServiceId(static::RESPONSE_CONTEXT_SERVICE_ID);

        // check if the requested Loa is supported
        $authnContextClassRef = $originalRequest->getAuthenticationContextClassRef();
        $failureResponse = $this->verifyAuthnContextClassRef(
          $authnContextClassRef,
          $logger
        );

        if ($failureResponse) {
            return $failureResponse;
        }

        $stateHandler->setRequestAuthnContextClassRef($authnContextClassRef);

        $proxyRequest = AuthnRequestFactory::createNewRequest(
            $this->get('surfnet_saml.hosted.service_provider'),
            $this->get('surfnet_saml.remote.idp')
        );

        $proxyRequest->setScoping([$originalRequest->getServiceProvider()]);
        $stateHandler->setGatewayRequestId($proxyRequest->getRequestId());

        $logger->notice(sprintf(
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
        $responseContext = $this->get(static::RESPONSE_CONTEXT_SERVICE_ID);
        $originalRequestId = $responseContext->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Received SAMLResponse, attempting to process for Proxy Response');

        try {
            $assertion = $this->get('surfnet_saml.http.post_binding')->processResponse(
                $request,
                $this->get('surfnet_saml.remote.idp'),
                $this->get('surfnet_saml.hosted.service_provider')
            );
        } catch (Exception $exception) {
            $logger->error(
              sprintf(
                'Could not process received Response, error: "%s"',
                $exception->getMessage()
              )
            );
            $responseRendering = $this->get('gateway.service.saml_response');
            return $responseRendering->renderUnprocessableResponse(
              $this->get(static::RESPONSE_CONTEXT_SERVICE_ID)
            );
        }

        $adaptedAssertion = new AssertionAdapter($assertion);
        $expectedInResponseTo = $responseContext->getExpectedInResponseTo();
        if (!$adaptedAssertion->inResponseToMatches($expectedInResponseTo)) {
            $logger->critical(sprintf(
                'Received Response with unexpected InResponseTo: "%s", %s',
                $adaptedAssertion->getInResponseTo(),
                ($expectedInResponseTo ? 'expected "' . $expectedInResponseTo . '"' : ' no response expected')
            ));

            return $this->render(
              'SurfnetStepupGatewayGatewayBundle:Gateway:unrecoverableError.html.twig'
            );
        }

        $logger->notice('Successfully processed SAMLResponse');

        $responseContext->saveAssertion($assertion);

        $logger->notice(sprintf('Forwarding to second factor controller for loa determination and handling'));

        return $this->forward(
          'SurfnetStepupGatewayGatewayBundle:Selection:selectSecondFactorForVerification'
        );
    }

    public function respondAction()
    {
        $responseContext = $this->get(static::RESPONSE_CONTEXT_SERVICE_ID);
        $originalRequestId = $responseContext->getInResponseTo();

        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Creating Response');

        $grantedLoa = null;
        if ($responseContext->isSecondFactorVerified()) {
            $secondFactor = $this->get('gateway.service.second_factor_service')->findByUuid(
                $responseContext->getSelectedSecondFactor()
            );

            $grantedLoa = $this->get('surfnet_stepup.service.loa_resolution')->getLoaByLevel(
                $secondFactor->getLoaLevel()
            );
        }

        $proxyResponseService = $this->get('gateway.service.response_proxy');
        $response             = $proxyResponseService->createProxyResponse(
            $responseContext->reconstituteAssertion(),
            $responseContext->getServiceProvider(),
            (string) $grantedLoa
        );

        $responseContext->responseSent();

        $logger->notice(sprintf(
            'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
            $responseContext->getInResponseTo(),
            $response->getId()
        ));

        $responseRendering = $this->get('gateway.service.saml_response');
        return $responseRendering->renderResponse($responseContext, $response);
    }

    /**
     * @param $authnContextClassRef
     * @param LoggerInterface $logger
     * @return null|Response
     */
    private function verifyAuthnContextClassRef(
      $authnContextClassRef,
      LoggerInterface $logger
    ) {
        if (!$authnContextClassRef) {
            return null;
        }

        $loaResolution = $this->get('surfnet_stepup.service.loa_resolution');
        if (!$loaResolution->hasLoa($authnContextClassRef)) {
            $logger->info(
              sprintf(
                'Requested required Loa "%s" does not exist, sending response with status Requester Error',
                $authnContextClassRef
              )
            );
            $responseRendering = $this->get(
              'gateway.service.saml_response'
            );

            return $responseRendering->renderRequesterFailureResponse(
              $this->get(self::RESPONSE_CONTEXT_SERVICE_ID)
            );
        }

        $loa = $loaResolution->getLoa($authnContextClassRef);
        $authContextClass = $loa->fetchAuthnContextClassOfType(
          AuthnContextClass::TYPE_GATEWAY
        );

        if (!$authContextClass->isIdentifiedBy($authnContextClassRef)) {
            $logger->info(
              sprintf(
                'Requested required Loa "%s" is of the wrong type, sending response with status Requester Error',
                $authnContextClassRef
              )
            );
            $responseRendering = $this->get(
              'gateway.service.saml_response'
            );

            return $responseRendering->renderRequesterFailureResponse(
              $this->get(self::RESPONSE_CONTEXT_SERVICE_ID)
            );
        }

        return null;
    }
}
