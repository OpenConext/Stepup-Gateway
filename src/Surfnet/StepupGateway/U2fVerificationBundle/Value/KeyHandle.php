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

final class KeyHandle
{
    /**
     * @var string
     */
    private $keyHandle;

    /**
     * @param string $keyHandle
     */
    public function __construct($keyHandle)
    {
        if (!is_string($keyHandle)) {
            throw InvalidArgumentException::invalidType('string', 'keyHandle', $keyHandle);
        }

        if ($keyHandle === '') {
            throw new InvalidArgumentException('Invalid Argument, parameter "keyHandle" may not be an empty string');
        }

        $this->keyHandle = $keyHandle;
    }

    /**
     * @param KeyHandle $otherKeyHandle
     * @return bool
     */
    public function equals(KeyHandle $otherKeyHandle)
    {
        return $this->keyHandle === $otherKeyHandle->keyHandle;
    }

    /**
     * @return string
     */
    public function getKeyHandle()
    {
        return $this->keyHandle;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // Doctrine needs to be able to convert identifier fields to strings.
        return $this->keyHandle;
    }
}
