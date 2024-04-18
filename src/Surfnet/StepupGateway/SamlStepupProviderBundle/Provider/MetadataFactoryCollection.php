<?php

/**
 * Copyright 2024 SURFnet bv
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

use Surfnet\SamlBundle\Metadata\MetadataFactory;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidConfigurationException;

class MetadataFactoryCollection
{
    private array $factories = [];

    public function add(string $name, MetadataFactory $factory)
    {
        if (array_key_exists($name, $this->factories)) {
            throw new InvalidConfigurationException(sprintf('The metadata factory for GSSP "%s" already exists.', $name));
        }
        $this->factories[$name] = $factory;
    }

    public function getByIdentifier(string $name): MetadataFactory
    {
        if (array_key_exists($name, $this->factories)) {
            return $this->factories[$name];
        }
        throw new InvalidConfigurationException(sprintf('There is no metadata factory available for provider: %s', $name));
    }
}
