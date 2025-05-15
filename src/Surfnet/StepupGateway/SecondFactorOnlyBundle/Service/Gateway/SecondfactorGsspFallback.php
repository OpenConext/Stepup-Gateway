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

use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactor\SecondFactorInterface;

class SecondfactorGsspFallback implements SecondFactorInterface
{
    private string $secondFactorType;
    private string $displayLocale;
    public const SECOND_FACTOR_ID = 'gssp_fallback';

    private function __construct(string $secondFactorType, string $displayLocale)
    {
        $this->secondFactorType = $secondFactorType;
        $this->displayLocale = $displayLocale;
    }

    public static function create(string $type, string $displayLocale)
    {
        return new self($type, $displayLocale);
    }

    public function getSecondFactorId(): string
    {
        return self::SECOND_FACTOR_ID;
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
        return 'jane-a1@institution-a.example.com';
    }

    public function getInstitution(): string
    {
        return 'institution-a';
    }
}
