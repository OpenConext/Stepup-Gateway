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

class NullCookieValue implements CookieValueInterface
{
    public static function deserialize(string $serializedData): CookieValueInterface
    {
        return new self;
    }

    public function serialize(): string
    {
        return '';
    }

    public function meetsRequiredLoa(float $requiredLoa): bool
    {
        return false;
    }

    public function authenticationTime(): int
    {
        return -1;
    }

    public function secondFactorId(): string
    {
        return '';
    }

    public function getLoa(): float
    {
        return 0.0;
    }
}
