<?php

/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway;

use SAML2\Response;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Exception\InvalidSecondFactorMethodException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Saml\ResponseFactory;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaAliasLookupService;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Symfony\Component\HttpFoundation\Request;

class RespondService
{
    /** @var SamlAuthenticationLogger */
    private $samlLogger;

    /** @var LoaResolutionService */
    private $loaResolutionService;

    /** @var LoaAliasLookupService */
    private $loaAliasLookupService;

    /** @var ResponseFactory */
    private $responseFactory;

    /** @var SecondFactorService */
    private $secondFactorService;

    /** @var SecondFactorTypeService */
    private $secondFactorTypeService;

    /** @var ResponseValidator */
    private $responseValidator;

    public function __construct(
        SamlAuthenticationLogger $samlLogger,
        LoaResolutionService $loaResolutionService,
        LoaAliasLookupService $loaAliasLookupService,
        ResponseFactory $responseFactory,
        SecondFactorService $secondFactorService,
        SecondFactorTypeService $secondFactorTypeService,
        ResponseValidator $responseValidator
    ) {
        $this->samlLogger = $samlLogger;
        $this->loaResolutionService = $loaResolutionService;
        $this->loaAliasLookupService = $loaAliasLookupService;
        $this->responseFactory = $responseFactory;
        $this->secondFactorService = $secondFactorService;
        $this->secondFactorTypeService = $secondFactorTypeService;
        $this->responseValidator = $responseValidator;
    }


    /**
     * Send a SAML response back to the service provider.
     *
     * Second factor verification is finished. This method builds a AuthnResponse
     * to send back to the service provider in response to the AuthnRequest received in
     * the SecondFactorLoginService.
     *
     * @param ResponseContext $responseContext
     * @return Response
     */
    public function respond(ResponseContext $responseContext, Request $request)
    {
        $originalRequestId = $responseContext->getInResponseTo();
        $logger = $this->samlLogger->forAuthentication($originalRequestId);

        $logger->notice('Creating second-factor-only Response');

        $selectedSecondFactorUuid = $responseContext->getSelectedSecondFactor();
        if (!$selectedSecondFactorUuid) {
            throw new InvalidSecondFactorMethodException(
                'Cannot verify possession of an unknown second factor.'
            );
        }

        if (!$responseContext->isSecondFactorVerified()) {
            throw new InvalidSecondFactorMethodException(
                'Second factor was not verified'
            );
        }

        $secondFactor = $this->secondFactorService->findByUuid($selectedSecondFactorUuid);
        $this->responseValidator->validate($request, $secondFactor, $responseContext->getIdentityNameId());

        $grantedLoa = $this->loaResolutionService
            ->getLoaByLevel($secondFactor->getLoaLevel($this->secondFactorTypeService));

        $authnContextClassRef = $this->loaAliasLookupService->findAliasByLoa($grantedLoa);

        $response = $this->responseFactory->createSecondFactorOnlyResponse(
            $responseContext->getIdentityNameId(),
            $responseContext->getDestination(),
            $authnContextClassRef
        );

        $logger->notice(sprintf(
            'Responding to request "%s" with newly created response "%s"',
            $responseContext->getInResponseTo(),
            $response->getId()
        ));

        return $response;
    }

    /**
     * Reset the state of the response
     *
     * @param ResponseContext $responseContext
     */
    public function resetRespondState(ResponseContext $responseContext)
    {
        $responseContext->responseSent();
    }
}
