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
use ParagonIE\HiddenString\HiddenString;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\DecryptionFailedException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\EncryptionFailedException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\Configuration;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValueInterface;

class HaliteCryptoHelper implements CryptoHelperInterface
{
    private readonly EncryptionKey $encryptionKey;

    public function __construct(Configuration $configuration)
    {
        // The configured encryption key is used to create a Halite EncryptionKey
        $this->encryptionKey = new EncryptionKey(new HiddenString($configuration->getEncryptionKey()));
    }

    /**
     * Halite always uses authenticated encryption.
     * See: https://github.com/paragonie/halite/blob/v4.x/doc/Classes/Symmetric/Crypto.md#encrypt
     *
     * It uses XSalsa20 for encryption and BLAKE2b for message Authentication (MAC)
     * The keys used for encryption and message authentication are derived from the secret key using a
     * HKDF using a salt This means that learning either derived key cannot lead to learning the other
     * derived key, or the secret key input in the HKDF. Encrypting many messages using the same
     * secret key is not a problem in this design.
     */
    public function encrypt(CookieValueInterface $cookieValue): string
    {
        try {
            $plainTextCookieValue = new HiddenString($cookieValue->serialize());
            // Encryption (we use the default encoding: Halite::ENCODE_BASE64URLSAFE)
            $encryptedData = Crypto::encrypt(
                $plainTextCookieValue,
                $this->encryptionKey,
            );
        } catch (Exception $e) {
            throw new EncryptionFailedException(
                'Encrypting the CookieValue for failed',
                $e,
            );
        }
        return $encryptedData;
    }

    /**
     * Decrypt the cookie ciphertext back to plain text.
     * Again using the encryption key, used to encrypt the data.
     * The decrypt method will return a deserialized CookieValue value object
     */
    public function decrypt(string $cookieData): CookieValueInterface
    {
        try {
            // Decryption: (we use the default encoding: Halite::DECODE_BASE64URLSAFE)
            $decryptedData = Crypto::decrypt(
                $cookieData,
                $this->encryptionKey,
            );
        } catch (Exception $e) {
            throw new DecryptionFailedException(
                'Decrypting the CookieValue failed, see embedded error message for details',
                $e,
            );
        }
        return CookieValue::deserialize($decryptedData->getString());
    }
}
