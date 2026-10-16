<?php

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Provider;

use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidArgumentException;

final class AllowedServiceProviders implements \Stringable
{
    /** @var array */
    private $allowed;

    public function __construct(array $allowed)
    {
        foreach ($allowed as $serviceProvider) {
            if (!is_string($serviceProvider)) {
                throw InvalidArgumentException::invalidType('string', 'serviceProvider', $serviceProvider);
            }
        }
        $this->allowed = $allowed;
    }

    public function isConfigured(string $spEntityId): bool
    {
        return in_array($spEntityId, $this->allowed, true);
    }

    public function __toString(): string
    {
        return implode('", "', $this->allowed);
    }
}
