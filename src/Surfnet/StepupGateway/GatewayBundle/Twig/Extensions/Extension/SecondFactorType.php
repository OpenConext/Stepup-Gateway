<?php

/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\StepupGateway\GatewayBundle\Twig\Extensions\Extension;

use Surfnet\StepupBundle\Exception\InvalidArgumentException;
use Surfnet\StepupBundle\Service\SecondFactorTypeTranslationService;
use Surfnet\StepupBundle\Value\Provider\ViewConfigCollection;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException as GatewayInvalidArgumentException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ViewConfig;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final class SecondFactorType
{
    private string $logoFormat = '/images/second-factor/%s.png';

    public function __construct(
        private readonly SecondFactorTypeTranslationService $translator,
        private readonly ViewConfigCollection               $collection,
    ) {
    }

    public function getName(): string
    {
        return 'ra.twig.second_factor_type';
    }

    #[AsTwigFilter('trans_second_factor_type')]
    public function translateSecondFactorType($secondFactorType): string
    {
        return $this->translator->translate(
            $secondFactorType,
            'gateway.second_factor.search.type.%s'
        );
    }

    /**
     * Get the logo source for a second factor type. When GSSP, the logo source
     * is loaded from the view config object (derived from the yml config). When
     * a non gssp type is encountered a source is built based on the way these
     * logos are typically stored in the /web/images/second-factor folder
     */
    #[AsTwigFunction('second_factor_logo')]
    public function getSecondFactorTypeLogoByIdentifier($secondFactorType): string
    {
        try {
            /** @var ViewConfig $viewConfig */
            $viewConfig = $this->collection->getByIdentifier($secondFactorType);
            $logo = $viewConfig->getLogo();
        } catch (InvalidArgumentException $e) {
            // There is no view config for this second factor type,
            // indicating we are dealing with a hard coded second
            // factor provider (like sms or yubikey)
            $logo = sprintf($this->logoFormat, $secondFactorType);
        }

        if (empty($logo)) {
            throw new GatewayInvalidArgumentException(
                "Unable to find a logo for this second factor type {$secondFactorType}"
            );
        }

        return $logo;
    }
}
