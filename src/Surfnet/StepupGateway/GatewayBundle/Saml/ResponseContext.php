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
use Psr\Log\LoggerInterface;
use SAML2\Assertion;
use SAML2\XML\saml\Issuer;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\Exception\RuntimeException;
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
     * @var LoggerInterface
     */
    private $logger;

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
        ProxyStateHandler $stateHandler,
        LoggerInterface $logger,
        DateTime $now = null
    ) {
        $this->hostedIdentityProvider = $identityProvider;
        $this->samlEntityService      = $samlEntityService;
        $this->stateHandler           = $stateHandler;
        $this->logger                 = $logger;
        $this->generationTime         = is_null($now) ? new DateTime('now', new DateTimeZone('UTC')): $now;
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        $requestAcsUrl = $this->stateHandler->getRequestAssertionConsumerServiceUrl();

        return $this->getServiceProvider()->determineAcsLocation($requestAcsUrl, $this->logger);
    }

    /**
     * @return string
     */
    public function getDestinationForAdfs()
    {
        $requestAcsUrl = $this->stateHandler->getRequestAssertionConsumerServiceUrl();

        return $this->getServiceProvider()->determineAcsLocationForAdfs($requestAcsUrl);
    }

    public function getIssuer(): Issuer
    {
        $issuer = new Issuer();
        $issuer->setValue($this->hostedIdentityProvider->getEntityId());
        return $issuer;
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
     * @param Assertion $assertion
     */
    public function saveAssertion(Assertion $assertion)
    {
        $this->stateHandler->saveIdentityNameId($this->resolveNameIdValue($assertion));
        // same for the entityId of the authenticating Authority
        $authenticatingAuthorities = $assertion->getAuthenticatingAuthority();
        if (!empty($authenticatingAuthorities)) {
            $this->stateHandler->setAuthenticatingIdp(reset($authenticatingAuthorities));
        }

        // And also attempt to save the user's schacHomeOrganization
        $attributes = $assertion->getAttributes();
        if (!empty($attributes['urn:mace:terena.org:attribute-def:schacHomeOrganization'])) {
            $schacHomeOrganization = $attributes['urn:mace:terena.org:attribute-def:schacHomeOrganization'];
            $this->stateHandler->setSchacHomeOrganization(reset($schacHomeOrganization));
        }

        $this->stateHandler->saveAssertion($assertion->toXML()->ownerDocument->saveXML());
    }

    /**
     * @return Assertion
     */
    public function reconstituteAssertion()
    {
        $assertionAsXML    = $this->stateHandler->getAssertion();
        $assertionDocument = new DOMDocument();
        $assertionDocument->loadXML($assertionAsXML);

        return new Assertion($assertionDocument->documentElement);
    }

    /**
     * @return null|string
     */
    public function getIdentityNameId(): string
    {
        return $this->stateHandler->getIdentityNameId();
    }

    /**
     * Return the lower-cased schacHomeOrganization value from the assertion.
     *
     * Comparisons on SHO values should always be case insensitive. Stepup
     * configuration always contains SHO values lower-cased, so this getter
     * can be used to compare the SHO with configured values.
     *
     * @see StepUpAuthenticationService::resolveHighestRequiredLoa()
     *
     * @return null|string
     */
    public function getNormalizedSchacHomeOrganization()
    {
        return strtolower(
            $this->stateHandler->getSchacHomeOrganization()
        );
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
        $this->stateHandler->setPreferredLocale($secondFactor->displayLocale);
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

    public function finalizeAuthentication()
    {
        $this->stateHandler->setSelectedSecondFactorId(null);
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
        $this->stateHandler->setSecondFactorVerified(false);
    }

    /**
     * Retrieve the ResponseContextServiceId from state
     *
     * Used to determine we are dealing with a SFO or regular authentication. Both have different ResponseContext
     * instances, and it's imperative that successive consumers use the correct service.
     *
     * @return string|null
     */
    public function getResponseContextServiceId()
    {
        return $this->stateHandler->getResponseContextServiceId();
    }

    /**
     * Either gets the internal-collabPersonId if present or falls back on the regular name id attribute
     */
    private function resolveNameIdValue(Assertion $assertion): string
    {
        $attributes = $assertion->getAttributes();
        if (array_key_exists('urn:mace:surf.nl:attribute-def:internal-collabPersonId', $attributes)) {
            return reset($attributes['urn:mace:surf.nl:attribute-def:internal-collabPersonId']);
        }
        $nameId = $assertion->getNameId();
        if ($nameId && !is_null($nameId->getValue()) && is_string($nameId->getValue())) {
            return $nameId->getValue();
        }

        throw new RuntimeException('Unable to resolve an identifier from internalCollabPersonId or the Subject NameId');
    }

    public function isForceAuthn(): bool
    {
        return $this->stateHandler->isForceAuthn();
    }
}
