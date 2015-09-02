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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Value;

use Surfnet\StepupGateway\U2fVerificationBundle\Exception\InvalidArgumentException;

final class PublicKey
{
    /**
     * @var string
     */
    private $publicKey;

    /**
     * @param string $publicKey
     */
    public function __construct($publicKey)
    {
        if (!is_string($publicKey)) {
            throw InvalidArgumentException::invalidType('string', 'publicKey', $publicKey);
        }

        $this->publicKey = $publicKey;
    }

    /**
     * @param PublicKey $otherPublicKey
     * @return bool
     */
    public function equals(PublicKey $otherPublicKey)
    {
        return $this->publicKey === $otherPublicKey->publicKey;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }
}
