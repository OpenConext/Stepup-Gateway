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

use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidConfigurationException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\UnknownProviderException;
use function implode;

/**
 * @todo discuss (im)mutability
 */
final class ProviderRepository
{
    /**
     * @var []Provider
     */
    private $providers;

    public function __construct()
    {
        $this->providers = [];
    }

    /**
     * @param Provider $provider
     */
    public function addProvider(Provider $provider): void
    {
        if ($this->has($provider->getName())) {
            throw new InvalidConfigurationException(sprintf(
                'Provider "%s" has already been added to the repository',
                $provider->getName()
            ));
        }

        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * @param string $providerName
     * @return bool
     */
    public function has($providerName)
    {
        return array_key_exists($providerName, $this->providers);
    }

    /**
     * @param string $providerName
     * @return Provider
     */
    public function get($providerName)
    {
        if (!$this->has($providerName)) {
            throw UnknownProviderException::create($providerName, implode(', ', array_keys($this->providers)));
        }

        return $this->providers[$providerName];
    }
}
