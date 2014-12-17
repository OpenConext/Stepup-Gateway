<?php

/**
 * Copyright 2014 SURFnet bv
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
use Doctrine\ORM\EntityRepository;
use Surfnet\StepupGateway\GatewayBundle\Value\Loa;

class SecondFactorRepository extends EntityRepository
{
    public function getAllMatchingFor(Loa $highestLoa, $identityNameId)
    {
        /** @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor[] $secondFactors */
        $secondFactors = $this->createQueryBuilder('sf')
            ->where('sf.nameId = :nameId')
            ->setParameter('nameId', $identityNameId)
            ->getQuery()
            ->getResult();

        $matches = new ArrayCollection();
        foreach ($secondFactors as $secondFactor) {
            if ($secondFactor->canSatisfy($highestLoa)) {
                $matches->add($secondFactor);
            }
        }

        return $matches;
    }

    /**
     * @param string $secondFactorId
     * @return null|SecondFactor
     */
    public function findOneBySecondFactorId($secondFactorId)
    {
        return $this->findOneBy(['secondFactorId' => $secondFactorId]);
    }
}