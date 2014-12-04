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
use SAML2_Assertion;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Entity\ServiceProviderRepository;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;

class ResponseContext
{
    /**
     * @var IdentityProvider
     */
    private $hostedIdentityProvider;

    /**
     * @var ServiceProviderRepository
     */
    private $serviceProviderRepository;

    /**
     * @var ProxyStateHandler
     */
    private $stateHandler;

    /**
     * @var DateTime
     */
    private $generationTime;

    /**
     * @var ServiceProvider
     */
    private $targetServiceProvider;

    public function __construct(
        IdentityProvider $identityProvider,
        ServiceProviderRepository $serviceProviderRepository,
        ProxyStateHandler $stateHandler
    ) {
        $this->hostedIdentityProvider = $identityProvider;
        $this->serviceProviderRepository = $serviceProviderRepository;
        $this->stateHandler = $stateHandler;
        $this->generationTime = new DateTime('now', new DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        $serviceProvider = $this->getServiceProvider();

        if (!$serviceProvider) {
            return null;
        }

        return $serviceProvider->getAssertionConsumerUrl();
    }

    /**
     * @return null|string
     */
    public function getIssuer()
    {
        return $this->hostedIdentityProvider->getEntityId();
    }

    /**
     * @return int
     */
    public function getIssueInstant()
    {
        return $this->generationTime->getTimestamp();
    }

    /**
     * @return null|string
     */
    public function getInResponseTo()
    {
        return $this->stateHandler->getRequestId();
    }

    /**
     * @return null|string
     */
    public function getExpectedInResponseTo()
    {
        return $this->stateHandler->getGatewayRequestId();
    }

    /**
     * @return null|string
     */
    public function getRequiredLoa()
    {
        return $this->stateHandler->getRequestAuthContextClassRef();
    }

    /**
     * @return IdentityProvider
     */
    public function getIdentityProvider()
    {
        return $this->hostedIdentityProvider;
    }

    /**
     * @return null|ServiceProvider
     */
    public function getServiceProvider()
    {
        if (isset($this->targetServiceProvider)) {
            return $this->targetServiceProvider;
        }

        $serviceProviderId = $this->stateHandler->getRequestServiceProvider();

        return $this->targetServiceProvider = $this->serviceProviderRepository->getServiceProvider($serviceProviderId);
    }

    /**
     * @return null|string
     */
    public function getRelayState()
    {
        return $this->stateHandler->getRelayState();
    }

    /**
     * @param SAML2_Assertion $assertion
     */
    public function saveAssertion(SAML2_Assertion $assertion)
    {
        $this->stateHandler->saveAssertion($assertion->toXML()->ownerDocument->saveXML());
    }

    /**
     * @return SAML2_Assertion
     */
    public function reconstituteAssertion()
    {
        $assertionAsXML    = $this->stateHandler->getAssertion();
        $assertionDocument = new \DOMDocument();
        $assertionDocument->loadXML($assertionAsXML);

        return new SAML2_Assertion($assertionDocument->documentElement);
    }
}
