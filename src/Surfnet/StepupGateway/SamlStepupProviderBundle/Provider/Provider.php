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

use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;

final class Provider
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var \Surfnet\SamlBundle\Entity\IdentityProvider
     */
    private $identityProvider;

    /**
     * @var \Surfnet\SamlBundle\Entity\ServiceProvider
     */
    private $serviceProvider;

    /**
     * @var \Surfnet\SamlBundle\Entity\IdentityProvider
     */
    private $remoteIdentityProvider;

    /**
     * @var \Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler
     */
    private $stateHandler;

    public function __construct(
        $name,
        IdentityProvider $identityProvider,
        ServiceProvider $serviceProvider,
        IdentityProvider $remoteIdentityProvider,
        StateHandler $stateHandler
    ) {
        $this->name                    = $name;
        $this->identityProvider        = $identityProvider;
        $this->serviceProvider         = $serviceProvider;
        $this->remoteIdentityProvider  = $remoteIdentityProvider;
        $this->stateHandler            = $stateHandler;
    }

    /**
     * @return StateHandler
     */
    public function getStateHandler()
    {
        return $this->stateHandler;
    }

    /**
     * @return IdentityProvider
     */
    public function getRemoteIdentityProvider()
    {
        return $this->remoteIdentityProvider;
    }

    /**
     * @return ServiceProvider
     */
    public function getServiceProvider()
    {
        return $this->serviceProvider;
    }

    /**
     * @return IdentityProvider
     */
    public function getIdentityProvider()
    {
        return $this->identityProvider;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
