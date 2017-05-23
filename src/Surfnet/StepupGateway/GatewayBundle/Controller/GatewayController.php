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
    const RESPONSE_CONTEXT_SERVICE_ID = 'gateway.proxy.response_context';

    public function ssoAction(Request $httpRequest)
    {
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->get('logger');
        $logger->notice('Received AuthnRequest, started processing');

        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');

        try {
            $originalRequest = $redirectBinding->receiveSignedAuthnRequestFrom($httpRequest);
        } catch (Exception $e) {
            $logger->critical(sprintf('Could not process Request, error: "%s"', $e->getMessage()));

            return $this->render('unrecoverableError');
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
        $requiredLoa = $originalRequest->getAuthenticationContextClassRef();
        if ($requiredLoa && !$this->get('surfnet_stepup.service.loa_resolution')->hasLoa($requiredLoa)) {
            $logger->info(sprintf(
                'Requested required Loa "%s" does not exist, sending response with status Requester Error',
                $requiredLoa
            ));

            $response = $this->createRequesterFailureResponse();

            return $this->renderSamlResponse('consumeAssertion', $response);
        }

        $stateHandler->setRequiredLoaIdentifier($requiredLoa);

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
        $responseContext = $this->getResponseContext();
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

            $response = $this->createResponseFailureResponse($responseContext);

            return $this->renderSamlResponse('unprocessableResponse', $response);
        }

        $adaptedAssertion = new AssertionAdapter($assertion);

        if (!$adaptedAssertion->hasSubject()) {
            $logger->critical('Received Response without eduPersonTargetedID (EPTI)');
            $response = $this->createResponseFailureResponse(
                $responseContext,
                'The "urn:mace:dir:attribute-def:eduPersonTargetedID" is not present'
            );
            return $this->renderSamlResponse('unprocessableResponse', $response);
        }

        if (!$adaptedAssertion->hasSubjectNameId()) {
            $logger->critical('Received Response with missing NameId in eduPersonTargetedID (EPTI)');
            $response = $this->createResponseFailureResponse(
                $responseContext,
                'The "urn:mace:dir:attribute-def:eduPersonTargetedID" attribute does not contain a NameID with a value.'
            );
            return $this->renderSamlResponse('unprocessableResponse', $response);
        }

        $expectedInResponseTo = $responseContext->getExpectedInResponseTo();
        if (!$adaptedAssertion->inResponseToMatches($expectedInResponseTo)) {
            $logger->critical(sprintf(
                'Received Response with unexpected InResponseTo: "%s", %s',
                $adaptedAssertion->getInResponseTo(),
                ($expectedInResponseTo ? 'expected "' . $expectedInResponseTo . '"' : ' no response expected')
            ));

            return $this->render('unrecoverableError');
        }

        $logger->notice('Successfully processed SAMLResponse');

        $responseContext->saveAssertion($assertion);

        $logger->notice(sprintf('Forwarding to second factor controller for loa determination and handling'));

        return $this->forward('SurfnetStepupGatewayGatewayBundle:SecondFactor:selectSecondFactorForVerification');
    }

    public function respondAction()
    {
        $responseContext = $this->getResponseContext();
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

        return $this->renderSamlResponse('consumeAssertion', $response);
    }

    public function sendLoaCannotBeGivenAction()
    {
        $responseContext = $this->getResponseContext();
        $originalRequestId = $responseContext->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Loa cannot be given, creating Response with NoAuthnContext status');

        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('gateway.proxy.response_builder');

        $response = $responseBuilder
            ->createNewResponse($responseContext)
            ->setResponseStatus(SAML2_Const::STATUS_RESPONDER, SAML2_Const::STATUS_NO_AUTHN_CONTEXT)
            ->get();

        $logger->notice(sprintf(
            'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
            $responseContext->getInResponseTo(),
            $response->getId()
        ));

        return $this->renderSamlResponse('consumeAssertion', $response);
    }

    public function sendAuthenticationCancelledByUserAction()
    {
        $responseContext = $this->getResponseContext();
        $originalRequestId = $responseContext->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Authentication was cancelled by the user, creating Response with AuthnFailed status');

        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('gateway.proxy.response_builder');

        $response = $responseBuilder
            ->createNewResponse($responseContext)
            ->setResponseStatus(
                SAML2_Const::STATUS_RESPONDER,
                SAML2_Const::STATUS_AUTHN_FAILED,
                'Authentication cancelled by user'
            )
            ->get();

        $logger->notice(sprintf(
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
        $stateHandler = $this->get('gateway.proxy.state_handler');
        $responseContextServiceId = $stateHandler->getResponseContextServiceId();

        if (!$responseContextServiceId) {
            return $this->get(static::RESPONSE_CONTEXT_SERVICE_ID);
        }

        return $this->get($responseContextServiceId);
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
     * @param null $message     By default the messase is null, it can be used to specify the problem with the response
     * @return SAML2_Response
     */
    private function createResponseFailureResponse($context, $message = null)
    {
        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('gateway.proxy.response_builder');

        $response = $responseBuilder
            ->createNewResponse($context)
            ->setResponseStatus(SAML2_Const::STATUS_RESPONDER, null, $message)
            ->get();

        return $response;
    }
}
