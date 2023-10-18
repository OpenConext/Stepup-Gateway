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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Saml;

use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidSubjectException;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use function strtolower;

class StateHandler extends ProxyStateHandler
{
    /**
     * @var string
     */
    private $provider;

    /**
     * @var AttributeBagInterface
     */
    private $attributeBag;

    public function __construct(AttributeBagInterface $attributeBag, $provider)
    {
        $this->attributeBag = $attributeBag;
        $this->provider = $provider;
    }

    public function setSubject(string $subject): self
    {
        $currentSubject = $this->get('subject');
        if (!empty($currentSubject)) {
            if (strtolower($currentSubject) !== strtolower($subject)) {
                throw new InvalidSubjectException(
                    sprintf(
                        'The subject should not be rewritten with another value. Old: "%s", new "%s"',
                        $currentSubject,
                        $subject
                    )
                );
            }
        }
        $this->set('subject', $subject);

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->get('subject');
    }

    /**
     * @return bool
     */
    public function hasSubject()
    {
        return (bool) $this->getSubject();
    }

    public function markRequestAsSecondFactorVerification()
    {
        $this->set('is_second_factor_verification', true);

        return $this;
    }

    /**
     * @return bool
     */
    public function secondFactorVerificationRequested()
    {
        return (bool) $this->get('is_second_factor_verification');
    }

    public function clear()
    {
        $this->attributeBag->remove($this->provider);
    }

    protected function set($key, $value)
    {
        $this->attributeBag->set($this->provider . '/' . $key, $value);
    }

    protected function get($key)
    {
        return $this->attributeBag->get($this->provider . '/' . $key);
    }
}
