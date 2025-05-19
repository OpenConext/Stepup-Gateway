<?php

/**
 * Copyright 2025 SURFnet bv
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

use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Controller\SecondFactorController;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactor\SecondFactorInterface;
use Surfnet\StepupGateway\GatewayBundle\Service\WhitelistService;

class GsspFallbackService
{

    private SecondFactorRepository $secondFactorRepository;
    private ProxyStateHandler $stateHandler;
    private LoggerInterface $logger;

    public function __construct(SecondFactorRepository $secondFactorRepository, ProxyStateHandler $stateHandler, LoggerInterface $logger)
    {
        $this->secondFactorRepository = $secondFactorRepository;
        $this->stateHandler = $stateHandler;
        $this->logger = $logger;
    }

    /**
     * @param ReceivedAuthnRequest $originalRequest
     */
    public function handleSamlGsspExtension(ReceivedAuthnRequest $originalRequest): void
    {
        // todo: get extension data from authn request!
    }

    public function determineGsspFallbackNeeded(
        string $identityNameId,
        string $authenticationMode,
        Loa $requestedLoa,
        WhitelistService $whitelistService
    ): bool {

        return false;

        if ($authenticationMode === SecondFactorController::MODE_SFO) {
            return true;
        }

        return false;

        // - a LoA1.5 (i.e. self asserted) authentication is requested
        // - a fallback GSSP is configured
        // - this "fallback" option is enabled for the institution that the user belongs to.
        // - the configured user attribute is present in the AuthnRequest

//        $this->logger->info('Determine GSSP fallback');
//
//        $candidateSecondFactors = $this->secondFactorRepository->getInstitutionByNameId($identityNameId);
//        $this->logger->info(
//            sprintf('Loaded %d matching candidate second factors', count($candidateSecondFactors))
//        );
//
//        if ($candidateSecondFactors->isEmpty()) {
//            $this->logger->alert('No suitable candidate second factors found, sending Loa cannot be given response');
//        }

        return false;
    }

    public function isSecondFactorFallback(): bool
    {
        return $this->stateHandler->isSecondFactorFallback();
    }

    public function createSecondFactor(): SecondFactorInterface
    {
        return SecondfactorGsspFallback::create('azuremfa', $this->stateHandler->getPreferredLocale());
    }
}
