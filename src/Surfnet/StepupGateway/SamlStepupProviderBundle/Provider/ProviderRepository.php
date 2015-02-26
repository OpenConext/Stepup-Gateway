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

use Doctrine\Common\Collections\ArrayCollection;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidConfigurationException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\UnknownProviderException;

/**
 * @todo discuss (im)mutability
 */
final class ProviderRepository
{
    /**
     * @var <ArrayCollection>Provider
     */
    private $providers;

    public function __construct()
    {
        $this->providers = new ArrayCollection();
    }

    /**
     * @param Provider $provider
     */
    public function addProvider(Provider $provider)
    {
        if ($this->providers->containsKey($provider->getName())) {
            throw new InvalidConfigurationException(sprintf(
                'Provider "%s" has already been added to the repository',
                $provider->getName()
            ));
        }

        $this->providers->set($provider->getName(), $provider);
    }

    /**
     * @param string $providerName
     * @return bool
     */
    public function has($providerName)
    {
        return $this->providers->containsKey($providerName);
    }

    /**
     * @param string $providerName
     * @return Provider
     */
    public function get($providerName)
    {
        if (!$this->has($providerName)) {
            throw UnknownProviderException::create($providerName, $this->providers->getKeys());
        }

        return $this->providers->get($providerName);
    }
}
