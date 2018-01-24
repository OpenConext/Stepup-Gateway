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
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;

class DoctrineSecondFactorRepository extends EntityRepository implements SecondFactorRepository
{
    /**
     * @var SecondFactor[]
     */
    private $secondFactorsById = [];

    public function getAllMatchingFor(Loa $highestLoa, $identityNameId, SecondFactorTypeService $service)
    {
        /** @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor[] $secondFactors */
        $secondFactors = $this->findAllByIdentityNameId($identityNameId);

        $matches = new ArrayCollection();
        foreach ($secondFactors as $secondFactor) {
            if ($secondFactor->canSatisfy($highestLoa, $service)) {
                $matches->add($secondFactor);
            }
        }

        return $matches;
    }

    public function findOneBySecondFactorId($secondFactorId)
    {
        if (!isset($this->secondFactorsById[$secondFactorId])) {
            $this->secondFactorsById[$secondFactorId] = $this->findOneBy(['secondFactorId' => $secondFactorId]);
        }

        return $this->secondFactorsById[$secondFactorId];
    }

    /**
     * @param $identityNameId
     * @return string
     */
    public function getInstitutionByNameId($identityNameId)
    {
        /** @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor $secondFactor **/
        $secondFactor = $this->createQueryBuilder('sf')
            ->where('sf.nameId = :nameId')
            ->setParameter('nameId', $identityNameId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($secondFactor) {
            return $secondFactor->institution;
        }
        return '';
    }
}
