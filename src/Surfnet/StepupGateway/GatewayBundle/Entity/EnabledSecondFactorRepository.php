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

use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;

final class EnabledSecondFactorRepository implements SecondFactorRepository
{
    /**
     * @var string[]
     */
    private readonly array $enabledTypes;

    /**
     * @param string[]               $enabledTypes
     */
    public function __construct(
        private readonly SecondFactorRepository $secondFactorRepository,
        array $enabledTypes,
        private readonly LoggerInterface $logger,
    ) {
        $this->enabledTypes = array_combine($enabledTypes, $enabledTypes);
    }

    public function getAllMatchingFor(
        Loa $highestLoa,
        $identityNameId,
        SecondFactorTypeService $service,
    ): ArrayCollection {
        $enabledSecondFactors = new ArrayCollection();

        foreach ($this->secondFactorRepository->getAllMatchingFor($highestLoa, $identityNameId, $service) as $secondFactor) {
            if (!array_key_exists($secondFactor->secondFactorType, $this->enabledTypes)) {
                $this->logger->info(
                    sprintf(
                        'Discarding second factor; its second factor type "%s" is not enabled',
                        $secondFactor->secondFactorType,
                    ),
                );

                continue;
            }

            $enabledSecondFactors->add($secondFactor);
        }

        return $enabledSecondFactors;
    }

    public function findOneBySecondFactorId($secondFactorId)
    {
        $secondFactor = $this->secondFactorRepository->findOneBySecondFactorId($secondFactorId);

        if (!$secondFactor || !array_key_exists($secondFactor->secondFactorType, $this->enabledTypes)) {
            return null;
        }

        return $secondFactor;
    }

    public function getInstitutionByNameId($identityNameId)
    {
        return $this->secondFactorRepository->getInstitutionByNameId($identityNameId);
    }
}
