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

namespace Surfnet\StepupGateway\GatewayBundle\Service\SmsSecondFactor;

final class OtpVerification
{
    const STATUS_NO_MATCH = 0;
    const STATUS_MATCH_EXPIRED = 1;
    const STATUS_FOUND_MATCH = 2;
    const STATUS_TOO_MANY_ATTEMPTS = 3;

    /**
     * @var int
     */
    private $status;

    /**
     * @var null|string
     */
    private $phoneNumber;

    public static function noMatch()
    {
        return new self(self::STATUS_NO_MATCH);
    }

    public static function matchExpired()
    {
        return new self(self::STATUS_MATCH_EXPIRED);
    }

    public static function foundMatch($phoneNumber)
    {
        return new self(self::STATUS_FOUND_MATCH, $phoneNumber);
    }

    public static function tooManyAttempts()
    {
        return new self(self::STATUS_TOO_MANY_ATTEMPTS);
    }

    /**
     * @param int $status
     * @param string|null $phoneNumber
     */
    private function __construct($status, $phoneNumber = null)
    {
        $this->status = $status;
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return bool
     */
    public function wasSuccessful()
    {
        return $this->status === self::STATUS_FOUND_MATCH;
    }

    /**
     * @return bool
     */
    public function didOtpMatch()
    {
        return $this->status === self::STATUS_FOUND_MATCH || $this->status === self::STATUS_MATCH_EXPIRED;
    }
    /**
     * @return bool
     */
    public function didOtpExpire()
    {
        return $this->status === self::STATUS_MATCH_EXPIRED;
    }

    /**
     * @return bool
     */
    public function wasAttemptedTooManyTimes()
    {
        return $this->status === self::STATUS_TOO_MANY_ATTEMPTS;
    }

    /**
     * @return null|string Only guaranteed to be a string when status is successful.
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }
}
