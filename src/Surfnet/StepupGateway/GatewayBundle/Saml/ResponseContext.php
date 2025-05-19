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
use Exception;
use Psr\Log\LoggerInterface;
use SAML2\Assertion;
use SAML2\XML\saml\Issuer;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactor\SecondFactorInterface;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\Exception\AcsLocationNotAllowedException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\SecondfactorGsspFallback;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResponseContext
{
    /**
     * @var IdentityProvider
     */
    private $hostedIdentityProvider;

    /**
     * @var SamlEntityService
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
     * @var ServiceProvider
     */
    private $targetServiceProvider;

    /**
     * @throws Exception
     */
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
     * @return string|null
     */
    public function getDestination(): ?string
    {
        $requestAcsUrl = $this->stateHandler->getRequestAssertionConsumerServiceUrl();

        return $this->getServiceProvider()->determineAcsLocation($requestAcsUrl, $this->logger);
    }

    /**
     * @return string
     * @throws AcsLocationNotAllowedException
     */
    public function getDestinationForAdfs(): string
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
    public function getIssueInstant(): int
    {
        return $this->generationTime->getTimestamp();
    }

    /**
     * @return null|string
     */
    public function getInResponseTo(): ?string
    {
        return $this->stateHandler->getRequestId();
    }

    /**
     * @return null|string
     */
    public function getExpectedInResponseTo(): ?string
    {
        return $this->stateHandler->getGatewayRequestId();
    }

    /**
     * @return null|string
     */
    public function getRequiredLoa(): ?string
    {
        return $this->stateHandler->getRequiredLoaIdentifier();
    }

    /**
     * @return IdentityProvider
     */
    public function getIdentityProvider(): IdentityProvider
    {
        return $this->hostedIdentityProvider;
    }

    /**
     * @return null|ServiceProvider
     */
    public function getServiceProvider(): ?ServiceProvider
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
    public function getRelayState(): ?string
    {
        return $this->stateHandler->getRelayState();
    }

    /**
     * @param Assertion $assertion
     * @throws Exception
     */
    public function saveAssertion(Assertion $assertion): void
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
     * @throws Exception
     */
    public function reconstituteAssertion(): Assertion
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
     * Comparisons on SHO values should always be case-insensitive. Stepup
     * configuration always contains SHO values lower-cased, so this getter
     * can be used to compare the SHO with configured values.
     *
     * @see StepUpAuthenticationService::resolveHighestRequiredLoa()
     *
     * @return null|string
     */
    public function getNormalizedSchacHomeOrganization(): ?string
    {
        $schacHomeOrganization = $this->stateHandler->getSchacHomeOrganization();
        if ($schacHomeOrganization === null) {
            return null;
        }
        return strtolower($schacHomeOrganization);
    }

    /**
     * @param SecondFactor $secondFactor
     */
    public function saveSelectedSecondFactor(SecondFactorInterface $secondFactor): void
    {
        $this->stateHandler->setSelectedSecondFactorId($secondFactor->getSecondFactorId());
        $this->stateHandler->setSecondFactorVerified(false);
        $this->stateHandler->setSecondFactorIsFallback($secondFactor instanceof SecondfactorGsspFallback);
        $this->stateHandler->setPreferredLocale($secondFactor->getDisplayLocale());
    }

    public function getSelectedLocale(): string
    {
        return $this->stateHandler->getPreferredLocale();
    }

    /**
     * @return null|string
     */
    public function getSelectedSecondFactor(): ?string
    {
        return $this->stateHandler->getSelectedSecondFactorId();
    }

    public function markSecondFactorVerified(): void
    {
        $this->stateHandler->setSecondFactorVerified(true);
    }

    public function finalizeAuthentication(): void
    {
        // The second factor ID is used right before sending the response to verify if the SSO on
        // 2FA cookies Second Factor is still known on the platform That's why it is forgotten at
        // this point during authentication.
        $this->stateHandler->unsetSelectedSecondFactorId();
        // Right before sending the response, we check if we need to update the SSO on 2FA cookie
        // One of the triggers for storing a new cookie is if the authentication was performed with
        // a real Second Factor token. That's why this value is purged from state at this very late
        // point in time.
        $this->stateHandler->unsetVerifiedBySsoOn2faCookie();
    }

    /**
     * @return bool
     */
    public function isSecondFactorVerified(): bool
    {
        return $this->stateHandler->getSelectedSecondFactorId() && $this->stateHandler->isSecondFactorVerified();
    }

    public function getResponseAction(): ?string
    {
        return $this->stateHandler->getResponseAction();
    }

    /**
     * Resets some state after the response is sent
     * (e.g. resets which second factor was selected and whether it was verified).
     */
    public function responseSent(): void
    {
        $this->stateHandler->setSecondFactorVerified(false);
        $this->stateHandler->setSsoOn2faCookieFingerprint('');
        $this->stateHandler->setSecondFactorIsFallback(false);
    }

    /**
     * Retrieve the ResponseContextServiceId from state
     *
     * Used to determine we are dealing with an SFO or regular authentication. Both have different ResponseContext
     * instances, and it's imperative that successive consumers use the correct service.
     *
     * @return string|null
     */
    public function getResponseContextServiceId(): ?string
    {
        return $this->stateHandler->getResponseContextServiceId();
    }

    /**
     * Either gets the internal-collabPersonId if present or falls back on the regular name id attribute
     * @throws Exception
     */
    private function resolveNameIdValue(Assertion $assertion): string
    {
        $attributes = $assertion->getAttributes();
        if (array_key_exists('urn:mace:surf.nl:attribute-def:internal-collabPersonId', $attributes)) {
            return reset($attributes['urn:mace:surf.nl:attribute-def:internal-collabPersonId']);
        }
        $nameId = $assertion->getNameId();
        if ($nameId && is_string($nameId->getValue())) {
            return $nameId->getValue();
        }

        throw new RuntimeException('Unable to resolve an identifier from internalCollabPersonId or the Subject NameId');
    }

    public function isForceAuthn(): bool
    {
        return $this->stateHandler->isForceAuthn();
    }

    public function markVerifiedBySsoOn2faCookie(string $fingerprint): void
    {
        $this->stateHandler->setVerifiedBySsoOn2faCookie(true);
        $this->stateHandler->setSsoOn2faCookieFingerprint($fingerprint);
    }

    public function isVerifiedBySsoOn2faCookie(): bool
    {
        return $this->stateHandler->isVerifiedBySsoOn2faCookie();
    }
    public function getSsoOn2faCookieFingerprint(): bool
    {
        return $this->stateHandler->getSsoOn2faCookieFingerprint();
    }

    public function getAuthenticatingIdp(): ?string
    {
        return $this->stateHandler->getAuthenticatingIdp();
    }

    public function getRequestServiceProvider(): ?string
    {
        return $this->stateHandler->getRequestServiceProvider();
    }
}
