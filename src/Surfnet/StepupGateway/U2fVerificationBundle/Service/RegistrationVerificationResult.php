<?php

/**
 * Copyright 2015 SURFnet bv
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

use Surfnet\StepupGateway\U2fVerificationBundle\Entity\Registration;
use Surfnet\StepupGateway\U2fVerificationBundle\Exception\LogicException;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\PublicKey;
use Surfnet\StepupU2fBundle\Service\RegistrationVerificationResult as U2fRegistrationVerificationResult;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
final class RegistrationVerificationResult
{
    /**
     * Registration was a success.
     */
    const STATUS_SUCCESS = 'SUCCESS';

    /**
     * The response challenge did not match the request challenge.
     */
    const STATUS_UNMATCHED_REGISTRATION_CHALLENGE = 'UNMATCHED_REGISTRATION_CHALLENGE';

    /**
     * The response was signed by another party than the device, indicating it was tampered with.
     */
    const STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE = 'RESPONSE_NOT_SIGNED_BY_DEVICE';

    /**
     * The device has not been manufactured by a trusted party.
     */
    const STATUS_UNTRUSTED_DEVICE = 'UNTRUSTED_DEVICE';

    /**
     * The decoding of the device's public key failed.
     */
    const STATUS_PUBLIC_KEY_DECODING_FAILED = 'PUBLIC_KEY_DECODING_FAILED';

    /**
     * The U2F device reported an error.
     *
     * @see \Surfnet\StepupU2fBundle\Dto\RegisterResponse::$errorCode
     * @see \Surfnet\StepupU2fBundle\Dto\RegisterResponse::ERROR_CODE_* constants
     */
    const STATUS_DEVICE_ERROR = 'DEVICE_ERROR';

    /**
     * The AppIDs of the server and a message did not match.
     */
    const STATUS_APP_ID_MISMATCH = 'APP_ID_MISMATCH';

    /**
     * @var string
     */
    private $status;

    /**
     * @var Registration|null
     */
    private $registration;

    /**
     * @param U2fRegistrationVerificationResult $u2fResult
     * @return self
     */
    public static function from(U2fRegistrationVerificationResult $u2fResult)
    {
        $result = new self;

        if ($u2fResult->wasSuccessful()) {
            $result->status = self::STATUS_SUCCESS;
        } elseif ($u2fResult->didDeviceReportAnyError()) {
            $result->status = self::STATUS_DEVICE_ERROR;
        } elseif ($u2fResult->didResponseChallengeNotMatchRequestChallenge()) {
            $result->status = self::STATUS_UNMATCHED_REGISTRATION_CHALLENGE;
        } elseif ($u2fResult->wasResponseNotSignedByDevice()) {
            $result->status = self::STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE;
        } elseif ($u2fResult->canDeviceNotBeTrusted()) {
            $result->status = self::STATUS_UNTRUSTED_DEVICE;
        } elseif ($u2fResult->didPublicKeyDecodingFail()) {
            $result->status = self::STATUS_PUBLIC_KEY_DECODING_FAILED;
        } elseif ($u2fResult->didntAppIdsMatch()) {
            $result->status = self::STATUS_APP_ID_MISMATCH;
        } else {
            throw new LogicException('Unknown registration verification result status');
        }

        if ($u2fResult->wasSuccessful()) {
            $result->registration = new Registration(
                new KeyHandle($u2fResult->getRegistration()->keyHandle),
                new PublicKey($u2fResult->getRegistration()->publicKey)
            );
        }

        return $result;
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

    /**
     * @return null|Registration
     */
    public function getRegistration()
    {
        if (!$this->wasSuccessful()) {
            throw new LogicException('The registration was unsuccessful and the registration data is not available');
        }

        return $this->registration;
    }
}
