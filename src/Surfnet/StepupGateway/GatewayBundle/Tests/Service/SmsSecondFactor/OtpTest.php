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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Service\SmsSecondFactor;

use DateInterval;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupGateway\GatewayBundle\Service\SmsSecondFactor\Otp;

class OtpTest extends TestCase
{
    public function non_strings()
    {
        return [
            'array'        => [array()],
            'integer'      => [1],
            'object'       => [new \stdClass()],
            'null'         => [null],
            'bool'         => [false],
            'resource'     => [fopen('php://memory', 'w')],
        ];
    }

    public function non_non_empty_strings()
    {
        return [
            'empty string' => [''],
            'array'        => [array()],
            'integer'      => [1],
            'object'       => [new \stdClass()],
            'null'         => [null],
            'bool'         => [false],
            'resource'     => [fopen('php://memory', 'w')],
        ];
    }

    /**
     * @test
     * @group sms
     */
    public function can_be_created()
    {
        Otp::create('ABCDEFG', '123', new DateInterval('PT5M'));
    }

    /**
     * @test
     * @group sms
     * @dataProvider non_non_empty_strings
     * @param mixed $nonString
     */
    public function only_accepts_string_otps($nonString)
    {
        $this->setExpectedException(
            'Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException',
            'otpString'
        );
        Otp::create($nonString, '123', new DateInterval('PT5M'));
    }

    /**
     * @test
     * @group sms
     * @dataProvider non_non_empty_strings
     * @param mixed $nonString
     */
    public function only_accepts_string_phone_numbers($nonString)
    {
        $this->setExpectedException(
            'Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException',
            'phoneNumber'
        );
        Otp::create('ABCDEFG', $nonString, new DateInterval('PT5M'));
    }

    /**
     * @test
     * @group sms
     * @dataProvider non_strings
     * @param mixed $nonString
     */
    public function it_verifies_only_string_otps($nonString)
    {
        $this->setExpectedException(
            'Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException',
            'otpString'
        );
        $otp = Otp::create($nonString, '123', new DateInterval('PT5M'));
        $otp->verify($nonString);
    }
}
