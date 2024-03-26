<?php

/**
 * Copyright 2015 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Provider;

use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\UnknownProviderException;

final readonly class ConnectedServiceProviders
{
    public function __construct(private SamlEntityService $samlEntityService, private AllowedServiceProviders $allowed)
    {
    }

    public function isConnected(string $serviceProvider): bool
    {
        return $this->allowed->isConfigured($serviceProvider);
    }

    public function getConfigurationOf(string $serviceProvider): ServiceProvider
    {
        if (!$this->isConnected($serviceProvider)) {
            throw UnknownProviderException::create($serviceProvider, (string )$this->allowed);
        }

        return $this->samlEntityService->getServiceProvider($serviceProvider);
    }
}
