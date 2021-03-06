<?php

/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\StepupGateway\ApiBundle\Service;

use Surfnet\StepupGateway\ApiBundle\Dto\Otp as OtpDto;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Dto\YubikeyOtpVerificationResult;
use Surfnet\YubikeyApiClient\Service\OtpVerificationResult;

interface YubikeyServiceInterface
{
    /**
     * Verifies the OTP result status
     *
     * Returns an OtpVerificationResult which can be queried whether or not the OTP verification was successful.
     *
     * @param OtpDto $otp
     * @param Requester $requester
     * @return OtpVerificationResult
     */
    public function verifyOtp(OtpDto $otp, Requester $requester);

    /**
     * Verifies the OTP public id matches that of the registered token
     *
     * @param OtpDto $otp
     * @param $secondFactorIdentifier
     * @return YubikeyOtpVerificationResult
     */
    public function verifyPublicId(OtpDto $otp, $secondFactorIdentifier);
}
