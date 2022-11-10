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
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto\HaliteCryptoHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\DecryptionFailedException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;

/**
 * Integration test for the Crypto helper
 */
class HaliteCryptoHelperTest extends TestCase
{
    public function test_encrypt_decrypt_with_authentication()
    {
        $helper = new HaliteCryptoHelper();

        $cookie = $this->createCookieValue();
        $data = $helper->encrypt($cookie);
        $cookieDecrypted = $helper->decrypt($data);

        self::assertEquals($cookie, $cookieDecrypted);
    }

    public function test_encrypt_decrypt_with_authentication_decryption_impossible_if_tampered_with()
    {
        $helper = new HaliteCryptoHelper();

        $cookie = $this->createCookieValue();
        $data = $helper->encrypt($cookie);
        $data = substr($data, 1, strlen($data));
        self::expectException(DecryptionFailedException::class);
        $helper->decrypt($data);
    }

    private function createCookieValue(): CookieValue
    {
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->secondFactorId = 'abcdef-1234';
        $secondFactor->identityId = 'abcdef-1234';
        $loa = new Loa(3.0, 'LoA3');
        return CookieValue::from($secondFactor, $loa);
    }
}
