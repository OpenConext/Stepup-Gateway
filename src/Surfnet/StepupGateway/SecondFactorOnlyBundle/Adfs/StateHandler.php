<?php

/**
 * Copyright 2017 SURFnet bv
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class StateHandler
{
    const SESSION_PATH = 'surfnet/gateway/adfs';

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
     * @param string $authMethod
     * @return $this
     */
    public function setAuthMethod($authMethod)
    {
        $this->set('auth_method', $authMethod);

        return $this;
    }

    /**
     * @param string $context
     * @return $this
     */
    public function setContext($context)
    {
        $this->set('context', $context);

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getRequestId()
    {
        return $this->get('request_id');
    }

    /**
     * @return mixed|null
     */
    public function getAuthMethod()
    {
        return $this->get('auth_method');
    }

    /**
     * @return mixed|null
     */
    public function getContext()
    {
        return $this->get('context');
    }

    /**
     * @param string $requestId
     * @return bool
     */
    public function hasMatchingRequestId($requestId)
    {
        $requestIdFromSession = $this->get('request_id');
        if ($requestIdFromSession && $requestIdFromSession == $requestId) {
            return true;
        }

        return false;
    }

    /**
     * @param string $key
     * @param mixed $value Any scalar
     */
    protected function set($key, $value)
    {
        $this->session->set(self::SESSION_PATH . $key, $value);
    }

    /**
     * @param string $key
     * @return mixed|null Any scalar
     */
    protected function get($key)
    {
        return $this->session->get(self::SESSION_PATH . $key);
    }
}
