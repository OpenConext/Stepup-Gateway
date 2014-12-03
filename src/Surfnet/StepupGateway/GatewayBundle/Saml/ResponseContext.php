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

namespace Surfnet\StepupGateway\GatewayBundle\Saml;

use DateTime;
use DateTimeZone;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;

class ResponseContext
{
    private $hostedIdentityProvider;
    private $targetServiceProvider;
    private $generationTime;
    private $stateHandler;

    public function __construct(
        IdentityProvider $identityProvider,
        ServiceProvider $serviceProvider,
        ProxyStateHandler $stateHandler
    ) {
        $this->hostedIdentityProvider = $identityProvider;
        $this->targetServiceProvider = $serviceProvider;
        $this->stateHandler = $stateHandler;
        $this->generationTime = new DateTime('now', new DateTimeZone('UTC'));
    }

    public function getDestination()
    {
        return $this->targetServiceProvider->getAssertionConsumerUrl();
    }

    public function getIssuer()
    {
        return $this->hostedIdentityProvider->getEntityId();
    }

    public function getIssueInstant()
    {
        return $this->generationTime->getTimestamp();
    }

    public function getInResponseTo()
    {
        return $this->stateHandler->getRequestId();
    }
}
