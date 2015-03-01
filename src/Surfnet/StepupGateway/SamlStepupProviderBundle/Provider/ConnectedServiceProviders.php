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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Provider;

use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\UnknownProviderException;

/**
 * @todo discuss (im)mutability
 */
final class ConnectedServiceProviders
{
    private $connected;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService
     */
    private $samlEntityService;

    public function __construct(SamlEntityService $samlEntityService)
    {
        $this->samlEntityService = $samlEntityService;
        $this->connected = [];
    }

    /**
     * @param string $serviceProvider
     */
    public function connectServiceProvider($serviceProvider)
    {
        if (!is_string($serviceProvider)) {
            throw InvalidArgumentException::invalidType('string', 'serviceProvider', $serviceProvider);
        }

        $this->connected[] = $serviceProvider;
    }

    /**
     * @param string $serviceProvider
     * @return bool
     */
    public function isConnected($serviceProvider)
    {

        if (!is_string($serviceProvider)) {
            throw InvalidArgumentException::invalidType('string', 'serviceProvider', $serviceProvider);
        }
        return in_array($serviceProvider, $this->connected);
    }

    /**
     * @param string $serviceProvider
     * @return \Surfnet\SamlBundle\Entity\ServiceProvider
     */
    public function getConfigurationOf($serviceProvider)
    {
        if (!is_string($serviceProvider)) {
            throw InvalidArgumentException::invalidType('string', 'serviceProvider', $serviceProvider);
        }

        if (!$this->isConnected($serviceProvider)) {
            throw UnknownProviderException::create($serviceProvider, $this->connected);
        }

        return $this->samlEntityService->getServiceProvider($serviceProvider);
    }
}
