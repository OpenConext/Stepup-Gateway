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

namespace Surfnet\StepupGateway\GatewayBundle\Saml\Proxy;

use Surfnet\StepupGateway\GatewayBundle\Saml\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;

class ProxyStateHandler
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private $sessionPath,
    ) {
    }

    /**
     * Clear the complete state, leaving other states intact.
     */
    public function clear(): void
    {
        $all = $this->requestStack->getSession()->all();

        foreach (array_keys($all) as $key) {
            if (str_starts_with($key, $this->sessionPath)) {
                $this->requestStack->getSession()->remove($key);
            }
        }
    }

    public function setRequestId(string $originalRequestId): ProxyStateHandler
    {
        $this->set('request_id', $originalRequestId);

        return $this;
    }

    public function getRequestId(): ?string
    {
        return $this->get('request_id');
    }

    public function setRequestServiceProvider(string $serviceProvider): ProxyStateHandler
    {
        $this->set('service_provider', $serviceProvider);

        return $this;
    }

    public function getRequestServiceProvider(): ?string
    {
        return $this->get('service_provider');
    }

    public function setRequestAssertionConsumerServiceUrl(string $url): ProxyStateHandler
    {
        $this->set('assertion_consumer_service_url', $url);

        return $this;
    }

    public function getRequestAssertionConsumerServiceUrl(): ?string
    {
        return $this->get('assertion_consumer_service_url');
    }

    public function setRelayState(string $relayState): ProxyStateHandler
    {
        $this->set('relay_state', $relayState);

        return $this;
    }

    public function getRelayState(): ?string
    {
        return $this->get('relay_state');
    }

    public function setRequiredLoaIdentifier(?string $loaIdentifier): ProxyStateHandler
    {
        $this->set('loa_identifier', $loaIdentifier);

        return $this;
    }

    public function getRequiredLoaIdentifier(): ?string
    {
        return $this->get('loa_identifier');
    }

    public function setGatewayRequestId(string $requestId): ProxyStateHandler
    {
        $this->set('gateway_request_id', $requestId);

        return $this;
    }

    public function getGatewayRequestId(): ?string
    {
        return $this->get('gateway_request_id');
    }

    public function saveAssertion(string $assertionAsXmlString): ProxyStateHandler
    {
        $this->set('response_assertion', $assertionAsXmlString);

        return $this;
    }

    public function getAssertion(): ?string
    {
        return $this->get('response_assertion');
    }

    public function saveIdentityNameId(string $nameId): ProxyStateHandler
    {
        $this->set('name_id', $nameId);

        return $this;
    }

    public function getIdentityNameId(): string
    {
        $nameId = $this->get('name_id');
        if (!$nameId) {
            throw new RuntimeException('Unable to retrieve NameId, it was not set on the state handler');
        }

        if (!is_string($nameId)) {
            throw new RuntimeException(
                sprintf('Unable to retrieve NameId, must be a string, but a %s was set', gettype($nameId))
            );
        }
        return $nameId;
    }

    public function setAuthenticatingIdp(string $idpEntityId): ProxyStateHandler
    {
        $this->set('authenticating_idp', $idpEntityId);

        return $this;
    }

    public function getAuthenticatingIdp(): ?string
    {
        return $this->get('authenticating_idp');
    }

    public function setSelectedSecondFactorId(string $secondFactorId): ProxyStateHandler
    {
        $this->set('selected_second_factor', $secondFactorId);

        return $this;
    }

    public function unsetSelectedSecondFactorId(): void
    {
        $this->set('selected_second_factor', null);
    }

    public function getSelectedSecondFactorId(): ?string
    {
        return $this->get('selected_second_factor');
    }

    public function setSecondFactorVerified(bool $verified): ProxyStateHandler
    {
        $this->set('selected_second_factor_verified', $verified);

        return $this;
    }

    public function isSecondFactorVerified(): bool
    {
        return $this->get('selected_second_factor_verified') === true;
    }

    public function setVerifiedBySsoOn2faCookie(bool $isVerifiedByCookie): ProxyStateHandler
    {
        $this->set('verified_by_sso_on_2fa_cookie', $isVerifiedByCookie);

        return $this;
    }

    public function unsetVerifiedBySsoOn2faCookie(): void
    {
        $this->set('verified_by_sso_on_2fa_cookie', null);
    }

    public function isVerifiedBySsoOn2faCookie(): bool
    {
        return $this->get('verified_by_sso_on_2fa_cookie') === true;
    }

    public function setSsoOn2faCookieFingerprint(string $fingerprint): ProxyStateHandler
    {
        $this->set('sso_on_2fa_cookie_fingerprint', $fingerprint);

        return $this;
    }

    public function getSsoOn2faCookieFingerprint()
    {
        return $this->get('sso_on_2fa_cookie_fingerprint');
    }

    public function setResponseAction(string $controllerName): ProxyStateHandler
    {
        $this->set('response_controller', $controllerName);
        return $this;
    }

    public function getResponseAction(): ?string
    {
        return $this->get('response_controller');
    }

    public function setResponseContextServiceId(string $serviceId): ProxyStateHandler
    {
        $this->set('response_context_service_id', $serviceId);
        return $this;
    }

    public function getResponseContextServiceId(): ?string
    {
        return $this->get('response_context_service_id');
    }

    public function setSchacHomeOrganization($organization): ProxyStateHandler
    {
        $this->set('schac_home_organization', $organization);
        return $this;
    }

    public function setIsForceAuthn(bool $forceAuthn): ProxyStateHandler
    {
        $this->set('force_authn', $forceAuthn);
        return $this;
    }

    public function isForceAuthn(): bool
    {
        return $this->get('force_authn') === true;
    }

    public function getSchacHomeOrganization(): ?string
    {
        return $this->get('schac_home_organization');
    }


    public function setPreferredLocale($locale): ProxyStateHandler
    {
        $this->set('locale', $locale);
        return $this;
    }

    public function getPreferredLocale(): ?string
    {
        return $this->get('locale');
    }

    /**
     * note that the authentication mode is stored outside the session path, to enable other state handlers
     * to retrieve the Authentication state for a given authentication request id.
     *
     * @param $requestId
     * @param $authenticationMode
     */
    public function markAuthenticationModeForRequest($requestId, $authenticationMode): void
    {
        $this->requestStack->getSession()->set('surfnet/gateway/auth_mode/' . $requestId, $authenticationMode);
    }

    public function getAuthenticationModeForRequestId($requestId)
    {
        return $this->requestStack->getSession()->get('surfnet/gateway/auth_mode/' . $requestId);
    }

    protected function set($key, $value): void
    {
        $this->requestStack->getSession()->set($this->sessionPath . $key, $value);
    }

    protected function get(string $key): mixed
    {
        return $this->requestStack->getSession()->get($this->sessionPath . $key);
    }
}
