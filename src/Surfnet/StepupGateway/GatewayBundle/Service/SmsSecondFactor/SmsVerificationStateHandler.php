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

use Surfnet\StepupGateway\GatewayBundle\Service\Exception\TooManyChallengesRequestedException;

interface SmsVerificationStateHandler
{
    /**
     * @return bool
     */
    public function hasState();

    /**
     * @return void
     */
    public function clearState();

    /**
     * Generates a new OTP and returns it.
     *
     * @param string $phoneNumber
     * @return string
     * @throws TooManyChallengesRequestedException
     */
    public function requestNewOtp($phoneNumber);

    /**
     * @return int
     */
    public function getOtpRequestsRemainingCount();

    /**
     * @return int
     */
    public function getMaximumOtpRequestsCount();

    /**
     * Matches the given OTP with the currently stored SmsVerificationState. If it matches, the SmsVerificationState is
     * removed from storage. In all cases, the SmsVerificationState is returned if it was present.
     *
     * @param string $otp
     * @return OtpVerification
     */
    public function verify($otp);
}
