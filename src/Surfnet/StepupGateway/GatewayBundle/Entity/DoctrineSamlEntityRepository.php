<?php

/**
 * Copyright 2016 SURFnet bv
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
use Doctrine\ORM\Query\Parameter;

class DoctrineSamlEntityRepository extends EntityRepository implements SamlEntityRepository
{
    /**
     * @param $entityId
     * @return null|SamlEntity
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getIdentityProvider($entityId)
    {
        return $this
            ->createQueryBuilder('s')
            ->where('s.type = :entityType')
            ->andWhere('s.entityId = :entityId')
            ->setParameters(new ArrayCollection([new Parameter('entityType', SamlEntity::TYPE_IDP), new Parameter('entityId', $entityId)]))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param $entityId
     * @return null|SamlEntity
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getServiceProvider($entityId)
    {
        return $this
            ->createQueryBuilder('s')
            ->where('s.type = :entityType')
            ->andWhere('s.entityId = :entityId')
            ->setParameters(new ArrayCollection([new Parameter('entityType', SamlEntity::TYPE_SP), new Parameter('entityId', $entityId)]))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
