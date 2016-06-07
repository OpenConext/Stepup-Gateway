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

use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;

final class GatewaySamlEntityRepositoryDecorator implements SamlEntityRepository
{
    /**
     * @var SamlEntityRepository
     */
    private $repository;

    public function __construct(SamlEntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $entityId
     * @return null|SamlEntity
     */
    public function getIdentityProvider($entityId)
    {
        if (!is_string($entityId)) {
            throw InvalidArgumentException::invalidType('string', 'entityId', $entityId);
        }

        return $this->repository->getIdentityProvider($entityId);
    }

    /**
     * @param string $entityId
     * @return null|SamlEntity
     */
    public function getServiceProvider($entityId)
    {
        if (!is_string($entityId)) {
            throw InvalidArgumentException::invalidType('string', 'entityId', $entityId);
        }

        $serviceProvider = $this->repository->getServiceProvider($entityId);

        if (!$serviceProvider instanceof SamlEntity) {
            return null;
        }

        if (!$serviceProvider->toServiceProvider()->mayUseGateway()) {
            return null;
        }

        return $serviceProvider;
    }
}
