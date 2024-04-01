<?php

/**
 * Copyright 2015 SURFnet B.V.
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

namespace Surfnet\StepupGateway\GatewayBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;

interface SecondFactorRepository
{
    /**
     * @param string $identityNameId
     * @return Collection
     */
    public function getAllMatchingFor(Loa $highestLoa, string $identityNameId, SecondFactorTypeService $service): Collection;

    /**
     * Loads a second factor by its ID. Subsequent calls do not hit the database.
     *
     * @param string $secondFactorId
     * @return null|SecondFactor
     */
    public function findOneBySecondFactorId(string $secondFactorId): ?SecondFactor;

    /**
     * Load the institution for a given identity name id based on the vetted tokens.
     *
     * @param $identityNameId
     * @return string
     */
    public function getInstitutionByNameId(string $identityNameId): string;
}
