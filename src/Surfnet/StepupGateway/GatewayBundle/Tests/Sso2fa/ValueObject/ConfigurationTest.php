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

namespace Surfnet\StepupGateway\GatewayBundle\Test\Sso2fa\ValueObject;


use PHPUnit\Framework\TestCase;
use SodiumException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\InvalidCookieTypeException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\InvalidEncryptionKeyException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\Configuration;

class ConfigurationTest extends TestCase
{
    public function test_persistent_cookie_requires_non_zero_lifetime()
    {
        self::expectException(InvalidCookieTypeException::class);
        self::expectExceptionMessage('When using a persistent cookie, you must configure a non zero cookie lifetime');
        new Configuration('name', 'persistent', 0, 'LORUM IPSUM DOLOR SIT AMOR VINCIT OMIA');
    }

    public function test_encryption_key_must_be_hexadecimal()
    {
        self::expectException(SodiumException::class);
        self::expectExceptionMessage('invalid hex string');
        new Configuration('name', 'session', 0, 'Monkey nut Mies');
    }

    public function test_encryption_key_must_be_amply_strong()
    {
        self::expectException(InvalidEncryptionKeyException::class);
        self::expectExceptionMessage('The configured SSO on 2FA encryption key is not complex enough, should be at least 32 bytes.');
        new Configuration('name', 'session', 0, '0f0f0f');
    }
}
