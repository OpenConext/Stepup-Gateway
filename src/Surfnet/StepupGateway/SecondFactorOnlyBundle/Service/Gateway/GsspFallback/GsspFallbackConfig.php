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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\GsspFallback;

class GsspFallbackConfig
{
    private string $gssp;
    private string $subjectAttribute;
    private string $institutionAttribute;

    public function __construct(
        string $gssp,
        string $subjectAttribute,
        string $institutionAttribute,
    ) {
        $this->gssp = $gssp;
        $this->subjectAttribute = $subjectAttribute;
        $this->institutionAttribute = $institutionAttribute;
    }

    public function getInstitutionAttribute(): string
    {
        return $this->institutionAttribute;
    }

    public function getSubjectAttribute(): string
    {
        return $this->subjectAttribute;
    }

    public function getGssp(): string
    {
        return $this->gssp;
    }

    public function isConfigured(): bool
    {
        return !empty($this->gssp);
    }
}
