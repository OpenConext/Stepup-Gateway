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

use Generator;
use Mockery;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;

class CookieValueTest extends TestCase
{
    public function test_serialize()
    {
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->secondFactorId = 'abcdef-1234';
        $secondFactor->identityId = 'abcdef-1234';
        $loa = new Loa(3.0, 'LoA3');
        $cookie = CookieValue::from($secondFactor->identityId, $secondFactor->secondFactorId, $loa->getLevel());
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
        $cookie = CookieValue::from($secondFactor->identityId, $secondFactor->secondFactorId, $loa->getLevel());
        $serialized = $cookie->serialize();
        $cookieValue = CookieValue::deserialize($serialized);
        self::assertInstanceOf(CookieValue::class, $cookieValue);
    }

    /**
     * @dataProvider loaProvider
     */
    public function test_loa_can_be_tested_against_required_loa(float $requiredLoa, bool $expectedResult)
    {
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->secondFactorId = 'abcdef-1234';
        $secondFactor->identityId = 'abcdef-1234';
        $loa = new Loa(2.0, 'LoA3');
        $cookie = CookieValue::from($secondFactor->identityId, $secondFactor->secondFactorId, $loa->getLevel());

        self::assertEquals($expectedResult, $cookie->meetsRequiredLoa($requiredLoa));
    }

    public function loaProvider(): Generator
    {
        yield [1.0, true];
        yield [1.5, true];
        yield [2.0, true];
        yield [3.0, false];
    }
}
