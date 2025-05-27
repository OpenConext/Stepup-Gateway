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
use Surfnet\StepupGateway\GatewayBundle\Entity\InstitutionConfigurationRepository;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactor\SecondFactorInterface;
use Surfnet\StepupGateway\GatewayBundle\Service\WhitelistService;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\GsspFallback\GsspFallbackConfig;

class GsspFallbackService
{

    private SecondFactorRepository $secondFactorRepository;
    private InstitutionConfigurationRepository $institutionConfigurationRepository;
    private ProxyStateHandler $stateHandler;
    private GsspFallbackConfig $config;

    public function __construct(
        SecondFactorRepository $secondFactorRepository,
        InstitutionConfigurationRepository $institutionConfigurationRepository,
        ProxyStateHandler $stateHandler,
        GsspFallbackConfig $config,
    ) {
        $this->secondFactorRepository = $secondFactorRepository;
        $this->institutionConfigurationRepository = $institutionConfigurationRepository;
        $this->stateHandler = $stateHandler;
        $this->config = $config;
    }

    /**
     * @param ReceivedAuthnRequest $originalRequest
     */
    public function handleSamlGsspExtension(LoggerInterface $logger, ReceivedAuthnRequest $originalRequest): void
    {
        if (!$this->config->isConfigured()) {
            return;
        }

        $logger->info('GSSP fallback configured, parsing GSSP extension from AuthnRequest');

        if ($originalRequest->getExtensions()->hasGsspUserAttributesChunk()) {
            $logger->info(
                sprintf('GSSP extension found, setting user attributes in state')
            );

            $gsspUserAttributes = $originalRequest->getExtensions()->getGsspUserAttributesChunk();

            $subject = $gsspUserAttributes->getAttributeValue($this->config->getSubjectAttribute());
            $institution = $gsspUserAttributes->getAttributeValue($this->config->getInstitutionAttribute());

            $logger->info(
                sprintf(
                    'GSSP extension found, setting user attributes in state: subject: %s, institution: %s',
                    $subject,
                    $institution
                )
            );

            $this->stateHandler->setGsspUserAttributes($subject, $institution);
        }
    }

    public function determineGsspFallbackNeeded(
        string $identityNameId,
        string $authenticationMode,
        Loa $requestedLoa,
        WhitelistService $whitelistService,
        LoggerInterface $logger,
        string $locale,
    ): bool {

        // Determine if the GSSP fallback flow should be started based on the following conditions:
        // - the authentication mode is SFO
        // - a fallback GSSP is configured
        // - a LoA1.5 (i.e. self asserted) authentication is requested
        // - the GSSP user attributes are available in the AuthnRequest
        // - the GSSP institution in the extension is whitelisted
        // - this "fallback" option is enabled for the institution that the user belongs to.
        // - the user has no registered tokens

        if ($authenticationMode !== SecondFactorController::MODE_SFO) {
            $this->stateHandler->setSecondFactorIsFallback(false);
            return false;
        }

        if (!$this->config->isConfigured()) {
            $this->stateHandler->setSecondFactorIsFallback(false);
            return false;
        }

        if (!$requestedLoa->levelIsLowerOrEqualTo(Loa::LOA_SELF_VETTED)) {
            $logger->info('Gssp Fallback configured but not used, requested LoA is higher than self-vetted');
            $this->stateHandler->setSecondFactorIsFallback(false);
            return false;
        }

        $subject = $this->stateHandler->getGsspUserAttributeSubject();
        $institution = $this->stateHandler->getGsspUserAttributeInstitution();
        if (empty($subject) || empty($institution)) {
            $this->stateHandler->setSecondFactorIsFallback(false);
            $logger->info('Gssp Fallback configured but not used, GSSP user attributes are not set in AuthnRequest');
            return false;
        }

        if (!$whitelistService->contains($institution)) {
            $this->stateHandler->setSecondFactorIsFallback(false);
            $logger->info('Gssp Fallback configured but not used, GSSP institution is not whitelisted');
            return false;
        }

        $institutionConfiguration = $this->institutionConfigurationRepository->getInstitutionConfiguration($institution);
        if (!$institutionConfiguration->ssoRegistrationBypass) {
            $this->stateHandler->setSecondFactorIsFallback(false);
            $logger->info('Gssp Fallback configured but not used, GSSP fallback is not enabled for the institution');
            return false;
        }
        
        if ($this->secondFactorRepository->hasTokens($identityNameId)) {
            $this->stateHandler->setSecondFactorIsFallback(false);
            $logger->info('Gssp Fallback configured but not used, the identity has registered tokens');
            return false;
        }

        $logger->info('Gssp Fallback flow started');

        $this->stateHandler->setSecondFactorIsFallback(true);
        $this->stateHandler->setPreferredLocale($locale);

        return true;
    }

    public function isSecondFactorFallback(): bool
    {
        return $this->stateHandler->isSecondFactorFallback();
    }

    public function createSecondFactor(): SecondFactorInterface
    {
        return SecondfactorGsspFallback::create(
            $this->stateHandler->getGsspUserAttributeSubject(),
            $this->stateHandler->getGsspUserAttributeInstitution(),
            $this->config->getGssp(),
            (string)$this->stateHandler->getPreferredLocale()
        );
    }
}
