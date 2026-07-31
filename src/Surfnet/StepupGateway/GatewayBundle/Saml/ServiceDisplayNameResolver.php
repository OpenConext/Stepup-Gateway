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
     * @param DisplayName[] $fromRequest Fallback candidates already parsed from the
     *     AuthnRequest's own mdui:UIInfo (or from what was stored for it in session).
     */
    public function resolve(?string $spEntityId, array $fromRequest, string $locale): ?DisplayName
    {
        if (!$this->featureConfiguration->isServiceNameFromSamlAuthnRequestEnabled()) {
            return null;
        }

        if ($spEntityId !== null && $this->samlEntityService->hasServiceProvider($spEntityId)) {
            $serviceNames = $this->samlEntityService->getServiceProvider($spEntityId)->getServiceNames();
            if (!empty($serviceNames)) {
                return $this->selectByLocale($this->displayNamesFromLocaleMap($serviceNames), $locale);
            }
        }

        return $this->selectByLocale($fromRequest, $locale);
    }

    /**
     * Converts Middleware's locale => name map into the same DisplayName[] shape the
     * AuthnRequest source already comes in, so both sources can be reduced to one name by the
     * same selectByLocale() below — Middleware's map and the AuthnRequest's own DisplayName
     * list should be selected from with identical rules, not two separately maintained copies
     * of the same logic.
     *
     * @param array<string, string> $serviceNames
     * @return DisplayName[]
     */
    private function displayNamesFromLocaleMap(array $serviceNames): array
    {
        $displayNames = [];
        foreach ($serviceNames as $configuredLocale => $serviceName) {
            $displayNames[] = new DisplayName(DisplayName::normalizeLocale($configuredLocale), $serviceName);
        }
        return $displayNames;
    }

    /**
     * Reduces a DisplayName[] to at most one, matching $locale. Priority: exact primary-subtag
     * match, then 'en', then nothing — no "first available" fallback, since showing a name in a
     * language the user didn't ask for and may not understand defeats the point of a
     * locale-matched name.
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

        return null;
    }
}
