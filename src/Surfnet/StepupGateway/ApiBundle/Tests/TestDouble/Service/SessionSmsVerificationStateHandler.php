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

use DateInterval;
use Mockery as m;
use Surfnet\StepupBundle\Service\Exception\TooManyChallengesRequestedException;
use Surfnet\StepupBundle\Service\SmsSecondFactor\OtpVerification;
use Surfnet\StepupBundle\Service\SmsSecondFactor\SmsVerificationStateHandler;
use Surfnet\StepupGateway\ApiBundle\Dto\Otp;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SessionSmsVerificationStateHandler implements SmsVerificationStateHandler
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public function hasState(string $secondFactorId): bool
    {
        return $this->session->has($secondFactorId);
    }

    /**
     * @inheritDoc
     */
    public function clearState(string $secondFactorId)
    {
        $this->session->remove($secondFactorId);
    }

    /**
     * The OTP is a combination of the phone number and the SecondFactorId
     */
    public function requestNewOtp(string $phoneNumber, string $secondFactorId): string
    {
        return sprintf("%s-%s", $phoneNumber, $secondFactorId);
    }

    /**
     * @inheritDoc
     */
    public function getOtpRequestsRemainingCount(string $secondFactorId): int
    {
        return 3;
    }

    /**
     * @inheritDoc
     */
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
