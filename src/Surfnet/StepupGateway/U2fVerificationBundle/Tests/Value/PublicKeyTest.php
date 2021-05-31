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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Tests\Value;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\U2fVerificationBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\PublicKey;

final class PublicKeyTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ValueObjectDataProvider;

    /**
     * @test
     * @group value
     */
    public function it_can_be_created()
    {
        self::assertInstanceOf(PublicKey::class, new PublicKey('WIDU_'));
    }

    /**
     * @test
     * @dataProvider nonEmptyStrings
     * @group value
     *
     * @param string $string
     */
    public function it_accepts_strings_as_public_key($string)
    {
        self::assertInstanceOf(PublicKey::class, new PublicKey($string));
    }

    /**
     * @test
     * @dataProvider nonStrings
     * @group value
     *
     * @param mixed $nonString
     */
    public function it_doesnt_accept_non_strings_as_public_key($nonString)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/should be of type "string"/');
        new PublicKey($nonString);
    }

    /**
     * @test
     * @group value
     */
    public function it_doesnt_accept_an_empty_string_as_public_keyh()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/may not be an empty string/');
        new PublicKey('');
    }
}
