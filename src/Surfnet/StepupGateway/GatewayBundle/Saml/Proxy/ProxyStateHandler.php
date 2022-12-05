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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use function is_bool;

class ProxyStateHandler
{
    private $sessionPath;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session, $sessionPath)
    {
        $this->sessionPath = $sessionPath;
        $this->session = $session;
    }

    /**
     * Clear the complete state, leaving other states intact.
     */
    public function clear()
    {
        $all = $this->session->all();

        foreach (array_keys($all) as $key) {
            if (strpos($key, $this->sessionPath) === 0) {
                $this->session->remove($key);
            }
        }
    }

    /**
     * @param string $originalRequestId
     * @return $this
     */
    public function setRequestId($originalRequestId)
    {
        $this->set('request_id', $originalRequestId);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->get('request_id');
    }

    /**
     * @param string $serviceProvider
     * @return $this
     */
    public function setRequestServiceProvider($serviceProvider)
    {
        $this->set('service_provider', $serviceProvider);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequestServiceProvider()
    {
        return $this->get('service_provider');
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setRequestAssertionConsumerServiceUrl($url)
    {
        $this->set('assertion_consumer_service_url', $url);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequestAssertionConsumerServiceUrl()
    {
        return $this->get('assertion_consumer_service_url');
    }

    /**
     * @param string $relayState
     * @return $this
     */
    public function setRelayState($relayState)
    {
        $this->set('relay_state', $relayState);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRelayState()
    {
        return $this->get('relay_state');
    }

    /**
     * @param string $loaIdentifier
     * @return $this
     */
    public function setRequiredLoaIdentifier($loaIdentifier)
    {
        $this->set('loa_identifier', $loaIdentifier);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequiredLoaIdentifier()
    {
        return $this->get('loa_identifier');
    }

    /**
     * @param string $requestId
     * @return $this
     */
    public function setGatewayRequestId($requestId)
    {
        $this->set('gateway_request_id', $requestId);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getGatewayRequestId()
    {
        return $this->get('gateway_request_id');
    }

    /**
     * @param string $assertionAsXmlString
     * @return $this
     */
    public function saveAssertion($assertionAsXmlString)
    {
        $this->set('response_assertion', $assertionAsXmlString);

        return $this;
    }

    /**
     * @return null|string
     */
    public function getAssertion()
    {
        return $this->get('response_assertion');
    }

    public function saveIdentityNameId(string $nameId)
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

    /**
     * @param string $idpEntityId
     * @return $this
     */
    public function setAuthenticatingIdp($idpEntityId)
    {
        $this->set('authenticating_idp', $idpEntityId);

        return $this;
    }

    /**
     * @return null|string
     */
    public function getAuthenticatingIdp()
    {
        return $this->get('authenticating_idp');
    }

    /**
     * @param string|null $secondFactorId
     * @return $this
     */
    public function setSelectedSecondFactorId($secondFactorId)
    {
        $this->set('selected_second_factor', $secondFactorId);

        return $this;
    }

    /**
     * @return null|string
     */
    public function getSelectedSecondFactorId()
    {
        return $this->get('selected_second_factor');
    }

    /**
     * @param bool $verified
     * @return $this
     */
    public function setSecondFactorVerified($verified)
    {
        $this->set('selected_second_factor_verified', $verified);

        return $this;
    }

    /**
     * @return bool
     */
    public function isSecondFactorVerified()
    {
        return $this->get('selected_second_factor_verified') === true;
    }

    /**
     * @param string $controllerName
     * @return $this
     */
    public function setResponseAction($controllerName)
    {
        $this->set('response_controller', $controllerName);
        return $this;
    }
    /**
     * @return string|null
     */
    public function getResponseAction()
    {
        return $this->get('response_controller');
    }
    /**
     * @param string $serviceId
     * @return $this
     */
    public function setResponseContextServiceId($serviceId)
    {
        $this->set('response_context_service_id', $serviceId);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getResponseContextServiceId()
    {
        return $this->get('response_context_service_id');
    }

    /**
     * @param $organization
     * @return $this
     */
    public function setSchacHomeOrganization($organization)
    {
        $this->set('schac_home_organization', $organization);
        return $this;
    }


    public function setIsForceAuthn(bool $forceAuthn): self
    {
        $this->set('force_authn', $forceAuthn);
        return $this;
    }


    public function isForceAuthn(): bool
    {
        return $this->get('force_authn') === true;
    }
    /**
     * @return string|null
     */
    public function getSchacHomeOrganization()
    {
        return $this->get('schac_home_organization');
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function setPreferredLocale($locale)
    {
        $this->set('locale', $locale);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPreferredLocale()
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
    public function markAuthenticationModeForRequest($requestId, $authenticationMode)
    {
        $this->session->set('surfnet/gateway/auth_mode/' . $requestId, $authenticationMode);
    }

    public function getAuthenticationModeForRequestId($requestId)
    {
        return $this->session->get('surfnet/gateway/auth_mode/' . $requestId);
    }

    /**
     * @param string $key
     * @param mixed $value Any scalar
     */
    protected function set($key, $value)
    {
        $this->session->set($this->sessionPath . $key, $value);
    }

    /**
     * @param string $key
     * @return mixed|null Any scalar
     */
    protected function get($key)
    {
        return $this->session->get($this->sessionPath . $key);
    }
}
