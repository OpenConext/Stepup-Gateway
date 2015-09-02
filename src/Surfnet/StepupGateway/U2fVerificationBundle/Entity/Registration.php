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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Surfnet\StepupGateway\U2fVerificationBundle\Exception\DomainException;
use Surfnet\StepupGateway\U2fVerificationBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\PublicKey;

/**
 * @ORM\Entity
 */
class Registration
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="u2f_key_handle")
     *
     * @var KeyHandle
     */
    private $keyHandle;

    /**
     * @ORM\Column(type="u2f_public_key")
     *
     * @var PublicKey
     */
    private $publicKey;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $signCounter;

    /**
     * @param KeyHandle $keyHandle
     * @param PublicKey $publicKey
     */
    public function __construct(KeyHandle $keyHandle, PublicKey $publicKey)
    {
        $this->keyHandle   = $keyHandle;
        $this->publicKey   = $publicKey;
        $this->signCounter = 0;
    }

    /**
     * @param int $newSignCounter
     * @throws DomainException
     */
    public function authenticationWasVerified($newSignCounter)
    {
        if (!is_int($newSignCounter)) {
            throw InvalidArgumentException::invalidType('int', 'newSignCounter', $newSignCounter);
        }

        if ($newSignCounter <= $this->signCounter) {
            throw new DomainException(
                sprintf(
                    'An authentication matching this registration was verified, but the sign counter "%d" was lower ' .
                    'than or equal to the last known sign counter "%d". This registration must be invalidated.',
                    $newSignCounter,
                    $this->signCounter
                )
            );
        }

        $this->signCounter = $newSignCounter;
    }

    /**
     * @return KeyHandle
     */
    public function getKeyHandle()
    {
        return $this->keyHandle;
    }

    /**
     * @return PublicKey
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return int
     */
    public function getSignCounter()
    {
        return $this->signCounter;
    }
}
