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
use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Saml\ResponseFactory;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaAliasLookupService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SecondFactorOnlyController extends Controller
{
    const RESPONSE_CONTEXT_SERVICE_ID = 'second_factor_only.response_context';

    /**
     * @param Request $httpRequest
     * @return Response
     */
    public function ssoAction(Request $httpRequest)
    {
        $logger = $this->get('logger');

        if (!$this->getParameter('second_factor_only')) {
            $logger->notice(sprintf(
                'Access to %s denied, second_factor_only parameter set to false.',
                __METHOD__
            ));
            throw $this->createAccessDeniedException('Second Factor Only feature disabled');
        }

        $logger->notice(
            'Received AuthnRequest on second-factor-only endpoint, started processing'
        );

        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $redirectBinding = $this->get('second_factor_only.http.redirect_binding');

        try {
            $originalRequest = $redirectBinding->processSignedRequest($httpRequest);
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
            ->setResponseAction('SurfnetStepupGatewaySecondFactorOnlyBundle:SecondFactorOnly:respond')
            ->setResponseContextServiceId(static::RESPONSE_CONTEXT_SERVICE_ID);

        // Check if the NameID is provided and we may use it.
        $nameId = $originalRequest->getNameId();
        if (!$this->isNameIdAllowedToUseSecondFactorOnly($originalRequest->getServiceProvider(), $nameId, $logger)) {
            /** @var \Surfnet\StepupGateway\GatewayBundle\Service\ResponseRenderingService $responseRendering */
            $responseRendering = $this->get('second_factor_only.response_rendering');
            return $responseRendering->renderRequesterFailureResponse(
                $this->getResponseContext()
            );
        }
        $stateHandler->saveIdentityNameId($nameId);

        // Check if the requested Loa is provided and supported.
        $authnContextClassRef = $originalRequest->getAuthenticationContextClassRef();
        $loaId = $this->verifyAuthnContextClassRef($authnContextClassRef, $logger);
        if (!$loaId) {
            /** @var \Surfnet\StepupGateway\GatewayBundle\Service\ResponseRenderingService $responseRendering */
            $responseRendering = $this->get('second_factor_only.response_rendering');
            return $responseRendering->renderRequesterFailureResponse(
                $this->getResponseContext()
            );
        }
        $stateHandler->setRequiredLoaIdentifier($loaId);

        $logger->notice(
            'Forwarding to second factor controller for loa determination and handling'
        );
        return $this->forward(
            'SurfnetStepupGatewayGatewayBundle:SecondFactor:selectSecondFactorForVerification'
        );
    }

    /**
     * @param string $authnContextClassRef
     * @param LoggerInterface $logger
     * @return string|null
     */
    private function verifyAuthnContextClassRef(
        $authnContextClassRef,
        LoggerInterface $logger
    ) {
        if (!$authnContextClassRef) {
            $logger->info(
                'No LOA requested, sending response with status Requester Error'
            );
            return null;
        }

        /** @var LoaAliasLookupService $loaAliasLookup */
        $loaAliasLookup = $this->get('second_factor_only.loa_alias_lookup');
        $loaId = $loaAliasLookup->findLoaIdByAlias($authnContextClassRef);

        if (!$loaId) {
            $logger->info(sprintf(
                'Requested required Loa "%s" does not have a second factor alias,'
                .' sending response with status Requester Error',
                $authnContextClassRef
            ));
            return null;
        }

        $loaResolutionService = $this->get('surfnet_stepup.service.loa_resolution');

        if (!$loaResolutionService->hasLoa($loaId)) {
            $logger->info(sprintf(
                'Requested required Loa "%s" does not exist,'
                .' sending response with status Requester Error',
                $authnContextClassRef
            ));
            return null;
        }

        return $loaId;
    }

    /**
     * @param string $spEntityId
     * @param string $nameId
     * @param LoggerInterface $logger
     * @return bool
     */
    private function isNameIdAllowedToUseSecondFactorOnly($spEntityId, $nameId, LoggerInterface $logger)
    {
        if (!$nameId) {
            $logger->info(
                'No NameID provided, sending response with status Requester Error'
            );
            return false;
        }

        $entityService = $this->get('second_factor_only.entity_service');
        $serviceProvider = $entityService->getServiceProvider($spEntityId);

        if (!$serviceProvider->isAllowedToUseSecondFactorOnlyFor($nameId)) {
            $logger->info(
                'No NameID provided, sending response with status Requester Error'
            );
            return false;
        }

        return true;
    }

    /**
     * @return Response
     */
    public function respondAction()
    {
        $responseContext = $this->getResponseContext();
        $originalRequestId = $responseContext->getInResponseTo();

        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        if (!$this->getParameter('second_factor_only')) {
            $logger->notice(sprintf(
                'Access to %s denied, second_factor_only parameter set to false.',
                __METHOD__
            ));
            throw $this->createAccessDeniedException('Second Factor Only feature disabled');
        }

        $logger->notice('Creating second-factor-only Response');

        $selectedSecondFactorUuid = $this->getResponseContext()->getSelectedSecondFactor();
        if (!$selectedSecondFactorUuid) {
            $logger->error(
                'Cannot verify possession of an unknown second factor'
            );

            throw new BadRequestHttpException('Cannot verify possession of an unknown second factor.');
        }

        if (!$responseContext->isSecondFactorVerified()) {
            $logger->error('Second factor was not verified');
            throw new BadRequestHttpException(
                'Cannot verify possession of an unknown second factor.'
            );
        }

        $secondFactor = $this->get('gateway.service.second_factor_service')
            ->findByUuid($selectedSecondFactorUuid);

        $grantedLoa = $this->get('surfnet_stepup.service.loa_resolution')
            ->getLoaByLevel($secondFactor->getLoaLevel());

        /** @var LoaAliasLookupService $loaAliasLookup */
        $loaAliasLookup = $this->get('second_factor_only.loa_alias_lookup');
        $authnContextClassRef = $loaAliasLookup->findAliasByLoa($grantedLoa);

        /** @var ResponseFactory $response_factory */
        $responseFactory = $this->get('second_factor_only.saml_response_factory');
        $response = $responseFactory->createSecondFactorOnlyResponse(
            $responseContext->getIdentityNameId(),
            $responseContext->getServiceProvider(),
            $authnContextClassRef
        );

        $responseContext->responseSent();

        $logger->notice(sprintf(
            'Responding to request "%s" with newly created response "%s"',
            $responseContext->getInResponseTo(),
            $response->getId()
        ));

        $responseRendering = $this->get('second_factor_only.response_rendering');
        return $responseRendering->renderResponse($responseContext, $response);
    }

    /**
     * @return \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext
     */
    public function getResponseContext()
    {
        return $this->get(static::RESPONSE_CONTEXT_SERVICE_ID);
    }
}
