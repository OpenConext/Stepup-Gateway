<?php

/**
 * Copyright 2026 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Saml;

use Surfnet\StepupGateway\GatewayBundle\Configuration\FeatureConfiguration;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;

// Priority: Middleware's service_name always wins over the AuthnRequest's own mdui:DisplayName.
class ServiceDisplayNameResolver
{
    public function __construct(
        private readonly FeatureConfiguration $featureConfiguration,
        private readonly SamlEntityService $samlEntityService
    ) {
    }

    /**
     * @param DisplayName[] $fromRequest
     */
    public function resolve(?string $spEntityId, array $fromRequest, string $locale): ?DisplayName
    {
        if ($spEntityId !== null && $this->samlEntityService->hasServiceProvider($spEntityId)) {
            $serviceNames = $this->samlEntityService->getServiceProvider($spEntityId)->getServiceNames();
            if (!empty($serviceNames)) {
                return $this->selectByLocale($this->displayNamesFromLocaleMap($serviceNames), $locale);
            }
        }

        if (!$this->featureConfiguration->isServiceNameFromSamlAuthnRequestEnabled()) {
            return null;
        }

        return $this->selectByLocale($fromRequest, $locale);
    }

    /**
     * @param array<string, string> $serviceNames
     * @return DisplayName[]
     */
    private function displayNamesFromLocaleMap(array $serviceNames): array
    {
        $displayNames = [];
        foreach ($serviceNames as $configuredLocale => $serviceName) {
            $displayNames[] = new DisplayName($configuredLocale, $serviceName);
        }
        return $displayNames;
    }

    /**
     * Picks the name matching $locale, else 'en', else the first one.
     *
     * @param DisplayName[] $displayNames
     */
    private function selectByLocale(array $displayNames, string $locale): ?DisplayName
    {
        if (empty($displayNames)) {
            return null;
        }

        $primarySubtag = DisplayName::normalizeLocale($locale);
        foreach ($displayNames as $displayName) {
            if (DisplayName::normalizeLocale($displayName->lang) === $primarySubtag) {
                return $displayName;
            }
        }
        foreach ($displayNames as $displayName) {
            if (DisplayName::normalizeLocale($displayName->lang) === 'en') {
                return $displayName;
            }
        }

        return $displayNames[0];
    }
}
