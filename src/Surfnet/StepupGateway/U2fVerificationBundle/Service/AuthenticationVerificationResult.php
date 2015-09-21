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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Service;

use Surfnet\StepupGateway\U2fVerificationBundle\Exception\LogicException;
use Surfnet\StepupU2fBundle\Service\AuthenticationVerificationResult as U2fAuthenticationVerificationResult;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
final class AuthenticationVerificationResult
{
    /**
     * Registration was a success.
     */
    const STATUS_SUCCESS = 'SUCCESS';

    /**
     * The response challenge did not match the request challenge.
     */
    const STATUS_REQUEST_RESPONSE_MISMATCH = 'REQUEST_RESPONSE_MISMATCH';

    /**
     * The response challenge was not for the given registration.
     */
    const STATUS_RESPONSE_REGISTRATION_MISMATCH = 'RESPONSE_REGISTRATION_MISMATCH';

    /**
     * The response was signed by another party than the device, indicating it was tampered with.
     */
    const STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE = 'RESPONSE_NOT_SIGNED_BY_DEVICE';

    /**
     * The decoding of the device's public key failed.
     */
    const STATUS_PUBLIC_KEY_DECODING_FAILED = 'PUBLIC_KEY_DECODING_FAILED';

    /**
     * The device sent a sign counter that was equal to or lower than the previously recorded counter.
     */
    const STATUS_SIGN_COUNTER_TOO_LOW = 'SIGN_COUNTER_TOO_LOW';

    /**
     * The U2F device reported an error.
     *
     * @see \Surfnet\StepupU2fBundle\Dto\SignResponse::$errorCode
     * @see \Surfnet\StepupU2fBundle\Dto\SignResponse::ERROR_CODE_* constants
     */
    const STATUS_DEVICE_ERROR = 'DEVICE_ERROR';

    /**
     * The AppIDs of the server and a message did not match.
     */
    const STATUS_APP_ID_MISMATCH = 'APP_ID_MISMATCH';

    /**
     * No registration matching the authentication's key handle could be found.
     */
    const STATUS_REGISTRATION_UNKNOWN = 'REGISTRATION_UNKNOWN';

    /**
     * @var string
     */
    private $status;

    /**
     * @param U2fAuthenticationVerificationResult $u2fResult
     * @return self
     */
    public static function from(U2fAuthenticationVerificationResult $u2fResult)
    {
        $result = new self;

        if ($u2fResult->wasSuccessful()) {
            $result->status = self::STATUS_SUCCESS;
        } elseif ($u2fResult->didDeviceReportAnyError()) {
            $result->status = self::STATUS_DEVICE_ERROR;
        } elseif ($u2fResult->didResponseChallengeNotMatchRequestChallenge()) {
            $result->status = self::STATUS_REQUEST_RESPONSE_MISMATCH;
        } elseif ($u2fResult->wasResponseNotSignedByDevice()) {
            $result->status = self::STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE;
        } elseif ($u2fResult->didPublicKeyDecodingFail()) {
            $result->status = self::STATUS_PUBLIC_KEY_DECODING_FAILED;
        } elseif ($u2fResult->wasSignCounterTooLow()) {
            $result->status = self::STATUS_SIGN_COUNTER_TOO_LOW;
        } elseif ($u2fResult->didntAppIdsMatch()) {
            $result->status = self::STATUS_APP_ID_MISMATCH;
        } else {
            throw new LogicException('Unknown authentication verification result status');
        }

        return $result;
    }

    /**
     * @return self
     */
    public static function registrationUnknown()
    {
        $result = new self;
        $result->status = self::STATUS_REGISTRATION_UNKNOWN;

        return $result;
    }

    private function __construct()
    {
    }

    /**
     * @return bool
     */
    public function wasSuccessful()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}
