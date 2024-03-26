<?php

/**
 * Copyright 2020 SURFnet bv
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

use Surfnet\StepupBundle\Service\SmsSecondFactor\OtpVerification;
use Surfnet\StepupBundle\Service\SmsSecondFactor\SmsVerificationStateHandler;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final readonly class SessionSmsVerificationStateHandler implements SmsVerificationStateHandler
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function hasState(string $secondFactorId): bool
    {
        return $this->requestStack->getSession()->has($secondFactorId);
    }

    public function clearState(string $secondFactorId): void
    {
        $this->requestStack->getSession()->remove($secondFactorId);
    }

    /**
     * The OTP is a combination of the phone number and the SecondFactorId
     */
    public function requestNewOtp(string $phoneNumber, string $secondFactorId): string
    {
        return sprintf("%s-%s", $phoneNumber, $secondFactorId);
    }

    public function getOtpRequestsRemainingCount(string $secondFactorId): int
    {
        return 3;
    }

    public function getMaximumOtpRequestsCount(): int
    {
        return 3;
    }

    /**
     * @inheritDoc
     */
    public function verify(string $otp, string $secondFactorId): OtpVerification
    {
        return OtpVerification::foundMatch('0606060606');
    }
}
