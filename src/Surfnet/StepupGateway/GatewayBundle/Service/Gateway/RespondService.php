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

namespace Surfnet\StepupGateway\GatewayBundle\Service\Gateway;

use SAML2\Response as SAMLResponse;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\ProxyResponseService;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;

class RespondService
{
    /** @var SamlAuthenticationLogger */
    private $samlLogger;

    /** @var LoaResolutionService */
    private $loaResolutionService;

    /** @var ProxyResponseService */
    private $responseProxy;

    /** @var SecondFactorService */
    private $secondFactorService;

    /** @var SecondFactorTypeService */
    private $secondFactorTypeService;

    /**
     * GatewayServiceProviderService constructor.
     * @param SamlAuthenticationLogger $samlLogger
     * @param LoaResolutionService $loaResolutionService
     * @param ProxyResponseService $responseProxy
     * @param SecondFactorService $secondFactorService
     * @param SecondFactorTypeService $secondFactorTypeService
     */
    public function __construct(
        SamlAuthenticationLogger $samlLogger,
        LoaResolutionService $loaResolutionService,
        ProxyResponseService $responseProxy,
        SecondFactorService $secondFactorService,
        SecondFactorTypeService $secondFactorTypeService
    ) {
        $this->samlLogger = $samlLogger;
        $this->loaResolutionService = $loaResolutionService;
        $this->responseProxy = $responseProxy;
        $this->secondFactorService = $secondFactorService;
        $this->secondFactorTypeService = $secondFactorTypeService;
    }

    /**
     * Send a SAML response back to the service provider.
     *
     * Second factor verification handled by the LoginService is
     * finished. This method sends a AuthnResponse back to the service
     * provider in response to the AuthnRequest received in the LoginService.
     * @param ResponseContext $responseContext
     * @return SAMLResponse
     */
    public function respond(ResponseContext $responseContext)
    {
        $originalRequestId = $responseContext->getInResponseTo();

        $logger = $this->samlLogger->forAuthentication($originalRequestId);
        $logger->notice('Creating Response');

        $grantedLoa = null;
        if ($responseContext->isSecondFactorVerified()) {
            $secondFactor = $this->secondFactorService->findByUuid(
                $responseContext->getSelectedSecondFactor()
            );

            $secondFactorTypeService = $this->secondFactorTypeService;
            $grantedLoa = $this->loaResolutionService->getLoaByLevel(
                $secondFactor->getLoaLevel($secondFactorTypeService)
            );
        }

        $response = $this->responseProxy->createProxyResponse(
            $responseContext->reconstituteAssertion(),
            $responseContext->getDestination(),
            (string)$grantedLoa
        );

        $logger->notice(sprintf(
            'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
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
