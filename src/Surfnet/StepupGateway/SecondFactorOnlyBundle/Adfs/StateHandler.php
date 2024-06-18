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

use Symfony\Component\HttpFoundation\RequestStack;

class StateHandler
{
    public const SESSION_PATH = 'surfnet/gateway/adfs';

    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function setRequestId(string $originalRequestId): StateHandler
    {
        $this->set('request_id', $originalRequestId);

        return $this;
    }

    public function setAuthMethod(string $authMethod): StateHandler
    {
        $this->set('auth_method', $authMethod);

        return $this;
    }

    public function setContext(string $context): StateHandler
    {
        $this->set('context', $context);

        return $this;
    }

    public function getRequestId(): mixed
    {
        return $this->get('request_id');
    }

    public function getAuthMethod(): mixed
    {
        return $this->get('auth_method');
    }

    public function getContext(): mixed
    {
        return $this->get('context');
    }

    public function hasMatchingRequestId(?string $requestId): bool
    {
        $requestIdFromSession = $this->get('request_id');
        if ($requestIdFromSession && $requestIdFromSession == $requestId) {
            return true;
        }

        return false;
    }

    protected function set(string $key, mixed $value): void
    {
        $this->requestStack->getSession()->set(self::SESSION_PATH . $key, $value);
    }

    protected function get(string $key): mixed
    {
        return $this->requestStack->getSession()->get(self::SESSION_PATH . $key);
    }
}
