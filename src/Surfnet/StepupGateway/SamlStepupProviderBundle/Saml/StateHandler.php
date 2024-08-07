<?php

/**
 * Copyright 2015 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Saml;

use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidSubjectException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class StateHandler extends ProxyStateHandler
{
    private const SESSION_PATH = 'surfnet/gateway/gssp';
    public function __construct(
        private RequestStack $requestStack,
        private readonly string $provider,
    ) {
    }

    public function setSubject(string $subject): self
    {
        $currentSubject = $this->get('subject');
        if (!empty($currentSubject) && strtolower($currentSubject) !== strtolower($subject)) {
            throw new InvalidSubjectException(
                sprintf(
                    'The subject should not be rewritten with another value. Old: "%s", new "%s"',
                    $currentSubject,
                    $subject
                )
            );
        }
        $this->set('subject', $subject);

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->get('subject');
    }

    /**
     * @return bool
     */
    public function hasSubject(): bool
    {
        return (bool) $this->getSubject();
    }

    public function markRequestAsSecondFactorVerification(): static
    {
        $this->set('is_second_factor_verification', true);

        return $this;
    }

    /**
     * @return bool
     */
    public function secondFactorVerificationRequested(): bool
    {
        return (bool) $this->get('is_second_factor_verification');
    }

    /**
     * Clear the complete state of this provider, leaving other provider (GSSP) states intact.
     */
    public function clear(): void
    {
        $all = $this->getSession()->all();
        $prefix = $this->getPrefix();
        foreach (array_keys($all) as $key) {
            if (str_starts_with($key, $prefix)) {
                $this->getSession()->remove($key);
            }
        }
    }

    protected function set($key, $value): void
    {
        $this->getSession()->set($this->getPrefix() . $key, $value);
    }

    protected function get($key): mixed
    {
        return $this->getSession()->get($this->getPrefix() . $key);
    }

    private function getPrefix(): string
    {
        return sprintf('%s/%s/', self::SESSION_PATH, $this->provider);
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
