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

namespace Surfnet\StepupGateway\ApiBundle\Service;

use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Value\YubikeyOtp;
use Surfnet\StepupBundle\Value\YubikeyPublicId;
use Surfnet\StepupGateway\ApiBundle\Dto\Otp as OtpDto;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Dto\YubikeyOtpVerificationResult;
use Surfnet\YubikeyApiClient\Otp;
use Surfnet\YubikeyApiClient\Service\OtpVerificationResult;
use Surfnet\YubikeyApiClientBundle\Service\VerificationService;

class YubikeyService implements YubikeyServiceInterface
{
    public function __construct(
        private readonly VerificationService $verificationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function verifyOtp(OtpDto $otp, Requester $requester): OtpVerificationResult
    {
        $this->logger->notice('Verifying Yubikey OTP.');

        if (!Otp::isValid($otp->value)) {
            return new OtpVerificationResult(OtpVerificationResult::ERROR_BAD_OTP);
        }

        $otp = Otp::fromString($otp->value);
        $result = $this->verificationService->verify($otp);

        if (!$result->isSuccessful()) {
            $this->logger->warning(sprintf('Yubikey OTP verification failed (%s)', $result->getError()));
        }

        return $result;
    }
    public function verifyPublicId(OtpDto $otp, string $secondFactorIdentifier): YubikeyOtpVerificationResult
    {
        $this->logger->notice('Verifying Yubikey OTP public id matches that of the second factor identifier');

        $otp = YubikeyOtp::fromString($otp->value);
        $publicId = YubikeyPublicId::fromOtp($otp);

        if (!$publicId->equals(new YubikeyPublicId($secondFactorIdentifier))) {
            $this->logger->warning('Yubikey OTP verification failed (Public Id did not match)');
            return new YubikeyOtpVerificationResult(
                YubikeyOtpVerificationResult::RESULT_PUBLIC_ID_DID_NOT_MATCH,
                $publicId
            );
        }

        return new YubikeyOtpVerificationResult(YubikeyOtpVerificationResult::RESULT_PUBLIC_ID_MATCHED, $publicId);
    }
}
