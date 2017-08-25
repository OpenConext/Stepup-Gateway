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
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Saml\ResponseFactory;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\AdfsHelper;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaAliasLookupService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SecondFactorOnlyController extends Controller
{
    /**
     * @param Request $httpRequest
     * @return Response
     */
    public function ssoAction(Request $httpRequest)
    {
        $logger = $this->get('logger');

        if (!$this->getParameter('second_factor_only')) {
            $logger->notice('Access to ssoAction denied, second_factor_only parameter set to false.');

            throw $this->createAccessDeniedException('Second Factor Only feature is disabled');
        }

        $logger->notice('Received AuthnRequest on second-factor-only endpoint, started processing');

        // ADFS support
        $adfsHelper = $this->get('second_factor_only.adfs.request_helper');
        if ($adfsHelper->isAdfsRequest($httpRequest)) {
            $logger->notice('Received AuthnRequest from an ADFS');
            try {
                $httpRequest = $adfsHelper->transformRequest($httpRequest);
            } catch (Exception $e) {
                $logger->critical(sprintf('Could not process ADFS Request, error: "%s"', $e->getMessage()));
                return $this->render('SurfnetStepupGatewayGatewayBundle:Gateway:unrecoverableError.html.twig');
            }
        }

        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $bindingFactory = $this->get('second_factor_only.http.binding_factory');

        try {
            $logger->notice('Determine what type of Binding is used in the Request');
            $binding = $bindingFactory->build($httpRequest);
            $originalRequest = $binding->receiveSignedAuthnRequestFrom($httpRequest);
        } catch (Exception $e) {
            $logger->critical(sprintf('Could not process Request, error: "%s"', $e->getMessage()));

            return $this->render('SurfnetStepupGatewayGatewayBundle:Gateway:unrecoverableError.html.twig');
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
            ->setResponseContextServiceId('second_factor_only.response_context');

        // Check if the NameID is provided and we may use it.
        $nameId = $originalRequest->getNameId();
        $secondFactorOnlyNameIdValidator = $this->get('second_factor_only.validate_nameid')->with($logger);
        $serviceProviderMayUseSecondFactorOnly = $secondFactorOnlyNameIdValidator->validate(
            $originalRequest->getServiceProvider(),
            $nameId
        );

        if (!$serviceProviderMayUseSecondFactorOnly) {
            /** @var \Surfnet\StepupGateway\GatewayBundle\Service\ResponseRenderingService $responseRendering */
            $responseRendering = $this->get('second_factor_only.response_rendering');

            return $responseRendering->renderRequesterFailureResponse($this->getResponseContext());
        }

        $stateHandler->saveIdentityNameId($nameId);

        // Check if the requested Loa is provided and supported.
        $loaId = $this->get('second_factor_only.loa_resolution')->with($logger)->resolve(
            $originalRequest->getAuthenticationContextClassRef()
        );

        if (empty($loaId)) {
            /** @var \Surfnet\StepupGateway\GatewayBundle\Service\ResponseRenderingService $responseRendering */
            $responseRendering = $this->get('second_factor_only.response_rendering');

            return $responseRendering->renderRequesterFailureResponse($this->getResponseContext());
        }

        $stateHandler->setRequiredLoaIdentifier($loaId);

        $logger->notice('Forwarding to second factor controller for loa determination and handling');

        return $this->forward('SurfnetStepupGatewayGatewayBundle:SecondFactor:selectSecondFactorForVerification');
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
        $secondFactorTypeService = $this->get('surfnet_stepup.service.second_factor_type');
        $grantedLoa = $this->get('surfnet_stepup.service.loa_resolution')
            ->getLoaByLevel($secondFactor->getLoaLevel($secondFactorTypeService));

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

        $adfsHelper = $this->get('second_factor_only.adfs.response_helper');
        if ($adfsHelper->isAdfsResponse($originalRequestId)) {
            $xmlResponse = $responseRendering->getResponseAsXML($response);
            try {
                $adfsParameters = $adfsHelper->retrieveAdfsParameters();
            } catch (Exception $e) {
                $logger->critical(sprintf('Could not process ADFS Response parameters, error: "%s"', $e->getMessage()));
                return $this->render('SurfnetStepupGatewayGatewayBundle:Gateway:unrecoverableError.html.twig');
            }

            $logger->notice('Sending ACS Response to ADFS plugin');
            return $this->render(
                '@SurfnetStepupGatewaySecondFactorOnly/Adfs/consumeAssertion.html.twig',
                [
                    'acu' => $responseContext->getDestination(),
                    'samlResponse' => $xmlResponse,
                    'context' => $adfsParameters->getContext(),
                    'authMethod' => $adfsParameters->getAuthMethod(),
                ]
            );
        }
        return $responseRendering->renderResponse($responseContext, $response);
    }

    /**
     * @return \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext
     */
    public function getResponseContext()
    {
        return $this->get('second_factor_only.response_context');
    }
}
