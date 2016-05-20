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
use Surfnet\SamlBundle\Http\XMLResponse;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GatewayController extends Controller
{
    public function metadataAction()
    {
        /** @var \Surfnet\SamlBundle\Metadata\MetadataFactory $metadataFactory */
        $metadataFactory = $this->get('surfnet_saml.metadata_factory');

        return new XMLResponse($metadataFactory->generate());
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
            ->setRelayState($httpRequest->get(AuthnRequest::PARAMETER_RELAY_STATE, ''));

        // check if the requested Loa is supported
        $requiredLoa = $originalRequest->getAuthenticationContextClassRef();
        if ($requiredLoa && !$this->get('surfnet_stepup.service.loa_resolution')->hasLoa($requiredLoa)) {
            $logger->info(sprintf(
                'Requested required Loa "%s" does not exist, sending response with status Requester Error',
                $requiredLoa
            ));
            return $this->get('gateway.service.saml_response')->renderRequesterFailureResponse();
        }

        $stateHandler->setRequestAuthnContextClassRef($originalRequest->getAuthenticationContextClassRef());

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
        $responseContext = $this->get('gateway.proxy.response_context');
        $originalRequestId = $responseContext->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Received SAMLResponse, attempting to process for Proxy Response');

        try {
            /** @var \SAML2_Assertion $assertion */
            $assertion = $this->get('surfnet_saml.http.post_binding')->processResponse(
                $request,
                $this->get('surfnet_saml.remote.idp'),
                $this->get('surfnet_saml.hosted.service_provider')
            );
        } catch (Exception $exception) {
            $logger->error(sprintf('Could not process received Response, error: "%s"', $exception->getMessage()));
            return $this->get('gateway.service.saml_response')->renderUnprocessableResponse();
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
        $responseContext = $this->get('gateway.proxy.response_context');
        $originalRequestId = $responseContext->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
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

        /** @var \Surfnet\StepupGateway\GatewayBundle\Service\ProxyResponseService $proxyResponseService */
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

        return $this->get('gateway.service.saml_response')->renderResponse($response);
    }
}
