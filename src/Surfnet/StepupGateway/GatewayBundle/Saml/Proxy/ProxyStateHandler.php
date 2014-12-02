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

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ProxyStateHandler
{
    const SESSION_PATH = 'surfnet/gateway/request';

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
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
     * @param string $authnContext
     * @return $this
     */
    public function setRequestAuthnContextClassRef($authnContext)
    {
        $this->set('authn_context', $authnContext);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequestAuthContextClassRef()
    {
        return $this->get('authn_context');
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
     * @param string $serviceProvider
     * @return $this
     */
    public function generateSessionIndex($serviceProvider)
    {
        $this->set('session_index', md5($serviceProvider . openssl_random_pseudo_bytes(40)));

        return $this;
    }

    /**
     * @return null|string
     */
    public function getSessionIndex()
    {
        return $this->get('session_index');
    }

    /**
     * @param string $key
     * @param string $value
     */
    private function set($key, $value)
    {
        $this->session->set(self::SESSION_PATH . $key, $value);
    }

    /**
     * @param string $key
     * @return string|null
     */
    private function get($key)
    {
        return $this->session->get(self::SESSION_PATH . $key);
    }
}
