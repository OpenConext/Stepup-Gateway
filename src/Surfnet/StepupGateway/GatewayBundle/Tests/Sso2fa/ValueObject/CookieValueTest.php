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

use Mockery;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\FingerprintNotValidException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;

class CookieValueTest extends TestCase
{
    public function test_serialize()
    {
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->secondFactorId = 'abcdef-1234';
        $secondFactor->identityId = 'abcdef-1234';
        $loa = new Loa(3.0, 'LoA3');
        $cookie = CookieValue::from($secondFactor, $loa);
        $serialized = $cookie->serialize();
        self::assertNotEmpty($serialized);
        self::assertIsString($serialized);
    }

    public function test_deserialization()
    {
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->secondFactorId = 'abcdef-1234';
        $secondFactor->identityId = 'abcdef-1234';
        $loa = new Loa(3.0, 'LoA3');
        $cookie = CookieValue::from($secondFactor, $loa);
        $serialized = $cookie->serialize();
        $cookieValue = CookieValue::deserialize($serialized);
        self::assertInstanceOf(CookieValue::class, $cookieValue);
    }

    public function test_fingerprint_is_verified_on_deserialization()
    {
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->secondFactorId = 'abcdef-1234';
        $secondFactor->identityId = 'abcdef-1234';
        $loa = new Loa(3.0, 'LoA3');
        $cookie = CookieValue::from($secondFactor, $loa);
        $serialized = $cookie->serialize();

        // Man in the middle somehow tampers with the contents of the cookie
        $serialized = str_replace('"loa":3', '"loa":1', $serialized);

        self::expectException(FingerprintNotValidException::class);
        CookieValue::deserialize($serialized);
    }
}
