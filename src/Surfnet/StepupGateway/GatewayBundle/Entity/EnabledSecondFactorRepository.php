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
use Surfnet\StepupBundle\Value\Loa;

final class EnabledSecondFactorRepository implements SecondFactorRepository
{
    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository
     */
    private $secondFactorRepository;

    /**
     * @var string[]
     */
    private $enabledTypes;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SecondFactorRepository $secondFactorRepository
     * @param string[]               $enabledTypes
     * @param LoggerInterface        $logger
     */
    public function __construct(
        SecondFactorRepository $secondFactorRepository,
        array $enabledTypes,
        LoggerInterface $logger
    ) {
        $this->secondFactorRepository = $secondFactorRepository;
        $this->enabledTypes = array_combine($enabledTypes, $enabledTypes);
        $this->logger = $logger;
    }

    public function getAllMatchingFor(Loa $highestLoa, $identityNameId)
    {
        $enabledSecondFactors = new ArrayCollection();

        foreach ($this->secondFactorRepository->getAllMatchingFor($highestLoa, $identityNameId) as $secondFactor) {
            if (!array_key_exists($secondFactor->secondFactorType, $this->enabledTypes)) {
                $this->logger->info(
                    sprintf(
                        'Discarding second factor; its second factor type "%s" is not enabled',
                        $secondFactor->secondFactorType
                    )
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

        if (!array_key_exists($secondFactor->secondFactorType, $this->enabledTypes)) {
            return null;
        }

        return $secondFactor;
    }
}
