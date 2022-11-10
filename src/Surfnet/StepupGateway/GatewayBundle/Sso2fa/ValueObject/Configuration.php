<?php declare(strict_types=1);

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

namespace Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject;

use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\InvalidCookieTypeException;

final class Configuration
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var CookieType
     */
    private $type;

    /**
     * @var int
     */
    private $lifetime;

    public function __construct(string $name, string $type, int $lifetime)
    {
        $this->name = $name;
        $this->type = CookieType::fromConfiguration($type);
        if ($lifetime === 0 && $this->type->isPersistent()) {
            throw new InvalidCookieTypeException(
                'When using a persistent cookie, you must configure a non zero cookie lifetime'
            );
        }
        $this->lifetime = $lifetime;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPersistent(): bool
    {
        return $this->type->isPersistent();
    }

    public function getLifetime(): int
    {
        return $this->lifetime;
    }
}
