<?php

declare(strict_types=1);

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

namespace Surfnet\StepupGateway\Behat\Mock;

use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactor\SecondFactorInterface;

readonly class MockSecondFactor implements SecondFactorInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $nameId,
        public string $institution,
        public string $displayLocale,
        public string $secondFactorId,
        public string $secondFactorType,
        public string $secondFactorIdentifier,
        public bool $identityVetted = true,
    ) {
    }


    public function canSatisfy(Loa $loa, SecondFactorTypeService $service): bool
    {
        return true;
    }

    public function getLoaLevel(SecondFactorTypeService $service): float
    {
        return 2.0;
    }

    public function getSecondFactorId(): string
    {
        return $this->secondFactorId;
    }

    public function getSecondFactorType(): string
    {
        return $this->secondFactorType;
    }

    public function getDisplayLocale(): string
    {
        return $this->displayLocale;
    }

    public function getSecondFactorIdentifier(): string
    {
        return $this->secondFactorIdentifier;
    }

    public function getInstitution(): string
    {
        return 'inst-mock';
    }
}
