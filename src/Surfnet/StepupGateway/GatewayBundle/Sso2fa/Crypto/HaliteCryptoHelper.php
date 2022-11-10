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

namespace Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto;

use Exception;
use ParagonIE\Halite\HiddenString;
use ParagonIE\Halite\Symmetric\AuthenticationKey;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\DecryptionFailedException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\EncryptionFailedException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;

class HaliteCryptoHelper implements CryptoHelperInterface
{
    private $authKey;

    public function __construct()
    {
        $this->authKey = new AuthenticationKey(new HiddenString(random_bytes(32)));
        $this->encryptionKey = new EncryptionKey(new HiddenString(random_bytes(32)));
    }

    public function encrypt(CookieValue $cookieValue): string
    {
        try {
            $encryptedData = Crypto::encrypt(new HiddenString($cookieValue->serialize()), $this->encryptionKey);
        } catch (Exception $e) {
            throw new EncryptionFailedException(
                sprintf('Encrypting the CookieValue for %s failed', $cookieValue->fingerprint()),
                $e
            );
        }
        return $encryptedData;
    }

    public function decrypt(string $cookieData): CookieValue
    {
        try {
            $decryptedData = Crypto::decrypt($cookieData, $this->encryptionKey);
        } catch (Exception $e) {
            throw new DecryptionFailedException(
                'Decrypting the CookieValue failed, see embedded error message for details',
                $e
            );
        }
        return CookieValue::deserialize($decryptedData->getString());
    }
}
