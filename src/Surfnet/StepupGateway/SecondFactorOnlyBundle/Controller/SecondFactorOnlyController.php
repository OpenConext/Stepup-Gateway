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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Controller;

use Exception;
use Surfnet\SamlBundle\Http\XMLResponse;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\StepupBundle\Value\AuthnContextClass;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SecondFactorOnlyController extends Controller
{
    const RESPONSE_CONTEXT_SERVICE_ID = 'second_factor_only.response_context';

    /**
     * @return XMLResponse
     */
    public function metadataAction()
    {
        return new XMLResponse(
          $this->get('second_factor_only.metadata_factory')->generate()
        );
    }

    /**
     * @param Request $httpRequest
     * @return Response
     */
    public function ssoAction(Request $httpRequest)
    {
        $logger = $this->get('logger');
        $logger->notice('Received AuthnRequest, started processing');

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

        $stateHandler = $this->get('gateway.proxy.state_handler');

        $stateHandler
          ->setRequestId($originalRequestId)
          ->setRequestServiceProvider($originalRequest->getServiceProvider())
          ->setRelayState($httpRequest->get(AuthnRequest::PARAMETER_RELAY_STATE, ''))
          ->setResponseAction('SurfnetStepupGatewayGatewayBundle:SecondFactorOnly:respond')
          ->setResponseContextServiceId(static::RESPONSE_CONTEXT_SERVICE_ID);

        if (!$originalRequest->getNameId()) {
            $logger->info(
              'No NameID provided, sending response with status Requester Error'
            );
            $responseRendering = $this->get('gateway.service.saml_response');
            return $responseRendering->renderRequesterFailureResponse(
              $this->get(static::RESPONSE_CONTEXT_SERVICE_ID)
            );
        }

        $stateHandler->saveIdentityNameId($originalRequest->getNameId());

        // check if the requested Loa is supported
        $authnContextClassRef = $originalRequest->getAuthenticationContextClassRef();

        if (!$authnContextClassRef) {
            $logger->info(
              'No LOA requested, sending response with status Requester Error'
            );
            $responseRendering = $this->get('gateway.service.saml_response');
            return $responseRendering->renderRequesterFailureResponse(
              $this->get(static::RESPONSE_CONTEXT_SERVICE_ID)
            );
        }

        $loaResolutionService = $this->get('surfnet_stepup.service.loa_resolution');
        $loa = $loaResolutionService->getLoa($authnContextClassRef);

        if (!$loa) {
            $logger->info(sprintf(
              'Requested required Loa "%s" does not exist,'
              .' sending response with status Requester Error',
              $authnContextClassRef
            ));
            $responseRendering = $this->get('gateway.service.saml_response');
            return $responseRendering->renderRequesterFailureResponse(
              $this->get(static::RESPONSE_CONTEXT_SERVICE_ID)
            );
        }

        $expectedContextClass = $loa->fetchAuthnContextClassOfType(
          AuthnContextClass::TYPE_SECOND_FACTOR_ONLY
        );

        if (!$expectedContextClass || !$expectedContextClass->isIdentifiedBy($authnContextClassRef)) {
            $logger->info(sprintf(
              'Requested required Loa "%s" does is of the wrong type!'
              . ' Please use 2nd-factor-only AuthnContextClassRefs.'
              . ' Sending response with status Requester Error',
              $authnContextClassRef
            ));
            $responseRendering = $this->get('gateway.service.saml_response');
            return $responseRendering->renderRequesterFailureResponse(
              $this->get(static::RESPONSE_CONTEXT_SERVICE_ID)
            );
        }

        $stateHandler->setRequestAuthnContextClassRef(
          $originalRequest->getAuthenticationContextClassRef()
        );

        $logger->notice(
          'Forwarding to second factor controller for loa determination and handling'
        );

        return $this->forward(
          'SurfnetStepupGatewayGatewayBundle:Selection:selectSecondFactorForVerification'
        );
    }

    /**
     * @return Response
     */
    public function respondAction()
    {
        $responseContext = $this->get(static::RESPONSE_CONTEXT_SERVICE_ID);
        $originalRequestId = $responseContext->getInResponseTo();

        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Creating Response');

        $secondFactorUuid = $this->get('gateway.service.require_selected_factor')
          ->requireSelectedSecondFactor($logger);

        if (!$responseContext->isSecondFactorVerified()) {
            $logger->error('Second factor was not verified');
            throw new BadRequestHttpException('Cannot verify possession of an unknown second factor.');
        }

        $secondFactor = $this->get('gateway.service.second_factor_service')->findByUuid(
          $secondFactorUuid
        );

        $grantedLoa = $this->get('surfnet_stepup.service.loa_resolution')->getLoaByLevel(
          $secondFactor->getLoaLevel()
        );

        $authnContextClass = $grantedLoa->fetchAuthnContextClassOfType(
          AuthnContextClass::TYPE_SECOND_FACTOR_ONLY
        );

        $response = $this->get('second_factor_only.response_proxy')
          ->create2ndFactorOnlyResponse(
              $responseContext->getIdentityNameId(),
              $responseContext->getServiceProvider(),
              (string) $authnContextClass
        );

        $responseContext->responseSent();

        $logger->notice(sprintf(
          'Responding to request "%s" with response based on '
          . 'response from the remote IdP with response "%s"',
          $responseContext->getInResponseTo(),
          $response->getId()
        ));

        $responseRendering = $this->get('gateway.service.saml_response');
        return $responseRendering->renderResponse($responseContext, $response);
    }
}
