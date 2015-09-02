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

use Surfnet\StepupGateway\U2fVerificationBundle\Dto\SignResponse;
use Surfnet\StepupGateway\U2fVerificationBundle\Exception\InvalidArgumentException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
final class AuthenticationVerificationResult
{
    /**
     * Registration was a success.
     */
    const STATUS_SUCCESS = 0;

    /**
     * The response challenge did not match the request challenge.
     */
    const STATUS_REQUEST_RESPONSE_MISMATCH = 1;

    /**
     * The response challenge was not for the given registration.
     */
    const STATUS_RESPONSE_REGISTRATION_MISMATCH = 2;

    /**
     * The response was signed by another party than the device, indicating it was tampered with.
     */
    const STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE = 3;

    /**
     * The decoding of the device's public key failed.
     */
    const STATUS_PUBLIC_KEY_DECODING_FAILED = 4;

    /**
     * @var int
     */
    private $status;

    /**
     * @var int|null
     */
    private $deviceErrorCode;

    /**
     * @return self
     */
    public static function success()
    {
        return new self(self::STATUS_SUCCESS);
    }

    /**
     * @param int $errorCode
     * @return self
     */
    public static function deviceReportedError($errorCode)
    {
        $validErrorCodes = [
            SignResponse::ERROR_CODE_OK,
            SignResponse::ERROR_CODE_OTHER_ERROR,
            SignResponse::ERROR_CODE_BAD_REQUEST,
            SignResponse::ERROR_CODE_CONFIGURATION_UNSUPPORTED,
            SignResponse::ERROR_CODE_DEVICE_INELIGIBLE,
            SignResponse::ERROR_CODE_TIMEOUT,
        ];

        if (!in_array($errorCode, $validErrorCodes, true)) {
            throw new InvalidArgumentException('Device error code is not one of the known error codes');
        }

        $result = new self(self::STATUS_DEVICE_ERROR);
        $result->deviceErrorCode = $errorCode;

        return $result;
    }

    /**
     * @return self
     */
    public static function requestResponseMismatch()
    {
        return new self(self::STATUS_REQUEST_RESPONSE_MISMATCH);
    }

    /**
     * @return self
     */
    public static function responseRegistrationMismatch()
    {
        return new self(self::STATUS_RESPONSE_REGISTRATION_MISMATCH);
    }

    /**
     * @return self
     */
    public static function responseWasNotSignedByDevice()
    {
        return new self(self::STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE);
    }

    /**
     * @return self
     */
    public static function publicKeyDecodingFailed()
    {
        return new self(self::STATUS_PUBLIC_KEY_DECODING_FAILED);
    }

    /**
     * @param int $status
     */
    private function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function wasSuccessful()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * @return bool
     */
    public function didDeviceReportABadRequest()
    {
        return $this->didDeviceReportError(RegisterResponse::ERROR_CODE_BAD_REQUEST);
    }

    /**
     * @return bool
     */
    public function wasClientConfigurationUnsupported()
    {
        return $this->didDeviceReportError(RegisterResponse::ERROR_CODE_CONFIGURATION_UNSUPPORTED);
    }

    /**
     * @return bool
     */
    public function wasKeyHandleUnknownToDevice()
    {
        return $this->didDeviceReportError(RegisterResponse::ERROR_CODE_DEVICE_INELIGIBLE);
    }

    /**
     * @return bool
     */
    public function didDeviceTimeOut()
    {
        return $this->didDeviceReportError(RegisterResponse::ERROR_CODE_TIMEOUT);
    }

    /**
     * @return bool
     */
    public function didDeviceReportAnUnknownError()
    {
        return $this->didDeviceReportError(RegisterResponse::ERROR_CODE_OTHER_ERROR);
    }

    /**
     * @return bool
     */
    public function didResponseChallengeNotMatchRequestChallenge()
    {
        return $this->status === self::STATUS_REQUEST_RESPONSE_MISMATCH;
    }

    /**
     * @return bool
     */
    public function didResponseChallengeNotMatchRegistration()
    {
        return $this->status === self::STATUS_RESPONSE_REGISTRATION_MISMATCH;
    }

    /**
     * @return bool
     */
    public function wasResponseNotSignedByDevice()
    {
        return $this->status === self::STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE;
    }

    /**
     * @return bool
     */
    public function didPublicKeyDecodingFail()
    {
        return $this->status === self::STATUS_PUBLIC_KEY_DECODING_FAILED;
    }

    /**
     * @param int $errorCode
     * @return bool
     */
    private function didDeviceReportError($errorCode)
    {
        return $this->status === self::STATUS_DEVICE_ERROR && $this->deviceErrorCode === $errorCode;
    }
}
