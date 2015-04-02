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

use DateInterval;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SessionSmsVerificationStateHandler implements SmsVerificationStateHandler
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @var DateInterval
     */
    private $otpExpiryInterval;

    /**
     * @var int
     */
    private $otpRequestMaximum;

    /**
     * @param SessionInterface $session
     * @param string $sessionKey
     * @param int $otpExpiryInterval OTP's expiry interval in seconds
     * @param int $otpRequestMaximum
     */
    public function __construct(
        SessionInterface $session,
        $sessionKey,
        $otpExpiryInterval,
        $otpRequestMaximum
    ) {
        $this->session = $session;
        $this->sessionKey = $sessionKey;
        $this->otpExpiryInterval = new DateInterval(sprintf('PT%dS', $otpExpiryInterval));
        $this->otpRequestMaximum = $otpRequestMaximum;
    }

    public function hasState()
    {
        return $this->session->has($this->sessionKey);
    }

    public function clearState()
    {
        $this->session->remove($this->sessionKey);
    }

    public function requestNewOtp($phoneNumber)
    {
        /** @var SmsVerificationState|null $state */
        $state = $this->session->get($this->sessionKey);

        if (!$state) {
            $state = new SmsVerificationState($this->otpExpiryInterval, $this->otpRequestMaximum);
            $this->session->set($this->sessionKey, $state);
        }

        return $state->requestNewOtp($phoneNumber);
    }

    public function getOtpRequestsRemainingCount()
    {
        /** @var SmsVerificationState|null $state */
        $state = $this->session->get($this->sessionKey);

        return $state ? $state->getOtpRequestsRemainingCount() : $this->otpRequestMaximum;
    }

    public function getMaximumOtpRequestsCount()
    {
        return $this->otpRequestMaximum;
    }

    public function verify($otp)
    {
        /** @var SmsVerificationState|null $state */
        $state = $this->session->get($this->sessionKey);

        if (!$state) {
            return OtpVerification::matchExpired();
        }

        $verification = $state->verify($otp);

        if ($verification->wasSuccessful()) {
            $this->session->remove($this->sessionKey);
        }

        return $verification;
    }
}
