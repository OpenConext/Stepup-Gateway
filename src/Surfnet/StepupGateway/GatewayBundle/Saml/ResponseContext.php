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
use DOMDocument;
use SAML2_Assertion;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;

class ResponseContext
{
    /**
     * @var IdentityProvider
     */
    private $hostedIdentityProvider;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService
     */
    private $samlEntityService;

    /**
     * @var ProxyStateHandler
     */
    private $stateHandler;

    /**
     * @var DateTime
     */
    private $generationTime;

    /**
     * @var IdentityProvider|null
     */
    private $authenticatingIdp;

    /**
     * @var ServiceProvider
     */
    private $targetServiceProvider;

    public function __construct(
        IdentityProvider $identityProvider,
        SamlEntityService $samlEntityService,
        ProxyStateHandler $stateHandler
    ) {
        $this->hostedIdentityProvider = $identityProvider;
        $this->samlEntityService      = $samlEntityService;
        $this->stateHandler           = $stateHandler;
        $this->generationTime         = new DateTime('now', new DateTimeZone('UTC'));
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
        return $this->stateHandler->getRequiredLoaIdentifier();
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

        return $this->targetServiceProvider = $this->samlEntityService->getServiceProvider($serviceProviderId);
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
        // we pluck the NameId to make it easier to access it without having to reconstitute the assertion
        $nameId = $assertion->getNameId();
        if (!empty($nameId['Value'])) {
            $this->stateHandler->saveIdentityNameId($nameId['Value']);
        }

        // same for the entityId of the authenticating Authority
        $authenticatingAuthorities = $assertion->getAuthenticatingAuthority();
        if (!empty($authenticatingAuthorities)) {
            $this->stateHandler->setAuthenticatingIdp(reset($authenticatingAuthorities));
        }

        $this->stateHandler->saveAssertion($assertion->toXML()->ownerDocument->saveXML());
    }

    /**
     * @return SAML2_Assertion
     */
    public function reconstituteAssertion()
    {
        $assertionAsXML    = $this->stateHandler->getAssertion();
        $assertionDocument = new DOMDocument();
        $assertionDocument->loadXML($assertionAsXML);

        return new SAML2_Assertion($assertionDocument->documentElement);
    }

    /**
     * @return null|string
     */
    public function getIdentityNameId()
    {
        return $this->stateHandler->getIdentityNameId();
    }

    /**
     * @return null|IdentityProvider
     */
    public function getAuthenticatingIdp()
    {
        $entityId = $this->stateHandler->getAuthenticatingIdp();

        if (!$entityId) {
            return null;
        }

        if (isset($this->authenticatingIdp)) {
            return $this->authenticatingIdp;
        }

        $this->authenticatingIdp = $this->samlEntityService->hasIdentityProvider($entityId)
            ? $this->samlEntityService->getIdentityProvider($entityId)
            : null;

        return $this->authenticatingIdp;
    }

    /**
     * @param SecondFactor $secondFactor
     */
    public function saveSelectedSecondFactor(SecondFactor $secondFactor)
    {
        $this->stateHandler->setSelectedSecondFactorId($secondFactor->secondFactorId);
        $this->stateHandler->setSecondFactorVerified(false);
    }

    /**
     * @return null|string
     */
    public function getSelectedSecondFactor()
    {
        return $this->stateHandler->getSelectedSecondFactorId();
    }

    public function markSecondFactorVerified()
    {
        $this->stateHandler->setSecondFactorVerified(true);
    }

    /**
     * @return bool
     */
    public function isSecondFactorVerified()
    {
        return $this->stateHandler->getSelectedSecondFactorId() && $this->stateHandler->isSecondFactorVerified();
    }

    public function getResponseAction()
    {
        return $this->stateHandler->getResponseAction();
    }

    /**
     * Resets some state after the response is sent
     * (e.g. resets which second factor was selected and whether it was verified).
     */
    public function responseSent()
    {
        $this->stateHandler->setSelectedSecondFactorId(null);
    }
}
