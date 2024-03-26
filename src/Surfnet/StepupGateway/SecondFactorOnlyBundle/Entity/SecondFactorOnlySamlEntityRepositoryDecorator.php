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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Entity;

use Surfnet\StepupGateway\GatewayBundle\Entity\SamlEntity;
use Surfnet\StepupGateway\GatewayBundle\Entity\SamlEntityRepository;

/**
 * @SuppressWarnings(PHPMD.LongClassName)
 */
final readonly class SecondFactorOnlySamlEntityRepositoryDecorator implements SamlEntityRepository
{
    public function __construct(private SamlEntityRepository $repository)
    {
    }

    public function getIdentityProvider($entityId)
    {
        return $this->repository->getIdentityProvider($entityId);
    }

    public function getServiceProvider($entityId): ?SamlEntity
    {
        $serviceProvider = $this->repository->getServiceProvider($entityId);

        if (!$serviceProvider instanceof SamlEntity) {
            return null;
        }

        if (!$serviceProvider->toServiceProvider()->mayUseSecondFactorOnly()) {
            return null;
        }

        return $serviceProvider;
    }
}
