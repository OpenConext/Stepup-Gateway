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

final class CookieType
{
    // A session cookie has no set expiration date. Once the browser window closes, the cookie is gone.
    private const TYPE_SESSION = 'session';
    // Persistent cookies have an expiration date, are stored at the client side and are usable until
    // the expiration date is reached.
    private const TYPE_PERSISTENT = 'persistent';

    private readonly string $type;

    private function __construct(string $type)
    {
        $allowedTypes = [self::TYPE_PERSISTENT, self::TYPE_SESSION];
        if (!in_array($type, $allowedTypes)) {
            throw new InvalidCookieTypeException(
                sprintf(
                    'The SSO on second factor authentication cookie type must be one of: "%s"',
                    implode(', ', $allowedTypes),
                ),
            );
        }
        $this->type = $type;
    }

    public static function fromConfiguration(string $type): self
    {
        return new self($type);
    }

    public function isPersistent(): bool
    {
        return $this->type === self::TYPE_PERSISTENT;
    }
}
