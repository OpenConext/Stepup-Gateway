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

namespace Surfnet\StepupGateway\ApiBundle\Tests\TestDouble\Service;

use Surfnet\StepupBundle\Value\YubikeyPublicId;
use Surfnet\StepupGateway\ApiBundle\Dto\Otp as OtpDto;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Service\YubikeyServiceInterface;
use Surfnet\StepupGateway\GatewayBundle\Service\StepUp\YubikeyOtpVerificationResult;

/**
 * Serves a test double for : ApiBundle/Service/YubikeyService
 *
 * This service will accept any OtpDto that it is fed, always returning a OtpVerificationResult with status STATUS_OK
 */
class YubikeyService implements YubikeyServiceInterface
{
    /**
     * @param OtpDto $otp
     * @param Requester $requester
     * @param Requester $secondFactorIdentifier
     * @return YubikeyOtpVerificationResult
     */
    public function verify(OtpDto $otp, Requester $requester, $secondFactorIdentifier)
    {
        return new YubikeyOtpVerificationResult(
            YubikeyOtpVerificationResult::RESULT_PUBLIC_ID_MATCHED,
            new YubikeyPublicId('12341234')
        );
    }
}
