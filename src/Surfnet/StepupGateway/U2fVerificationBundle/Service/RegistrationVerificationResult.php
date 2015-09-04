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
     * @var U2fRegistrationVerificationResult
     */
    private $wrappedResult;

    /**
     * @var Registration|null
     */
    private $registration;

    /**
     * @param U2fRegistrationVerificationResult $u2fResult
     * @return self
     */
    public static function wrap(U2fRegistrationVerificationResult $u2fResult)
    {
        $result = new self;
        $result->wrappedResult = $u2fResult;

        if ($u2fResult->wasSuccessful()) {
            $result->registration = new Registration(
                new KeyHandle($u2fResult->getRegistration()->keyHandle),
                new PublicKey($u2fResult->getRegistration()->publicKey)
            );
        }

        return $result;
    }

    /**
     * @return U2fRegistrationVerificationResult
     */
    public function wasSuccessful()
    {
        return $this->wrappedResult->wasSuccessful();
    }

    /**
     * @return Registration|null
     */
    public function getRegistration()
    {
        if (!$this->wrappedResult->wasSuccessful()) {
            throw new LogicException('The registration was unsuccessful and the registration data is not available');
        }

        return $this->registration;
    }

    /**
     * @return bool
     */
    public function didDeviceReportABadRequest()
    {
        return $this->wrappedResult->didDeviceReportABadRequest();
    }

    /**
     * @return bool
     */
    public function wasClientConfigurationUnsupported()
    {
        return $this->wrappedResult->wasClientConfigurationUnsupported();
    }

    /**
     * @return bool
     */
    public function wasDeviceAlreadyRegistered()
    {
        return $this->wrappedResult->wasDeviceAlreadyRegistered();
    }

    /**
     * @return bool
     */
    public function didDeviceTimeOut()
    {
        return $this->wrappedResult->didDeviceTimeOut();
    }

    /**
     * @return bool
     */
    public function didDeviceReportAnUnknownError()
    {
        return $this->wrappedResult->didDeviceReportAnUnknownError();
    }

    /**
     * @return bool
     */
    public function didDeviceReportAnyError()
    {
        return $this->wrappedResult->didDeviceReportAnyError();
    }

    /**
     * @return bool
     */
    public function didResponseChallengeNotMatchRequestChallenge()
    {
        return $this->wrappedResult->didResponseChallengeNotMatchRequestChallenge();
    }

    /**
     * @return bool
     */
    public function wasResponseNotSignedByDevice()
    {
        return $this->wrappedResult->wasResponseNotSignedByDevice();
    }

    /**
     * @return bool
     */
    public function canDeviceNotBeTrusted()
    {
        return $this->wrappedResult->canDeviceNotBeTrusted();
    }

    /**
     * @return bool
     */
    public function didPublicKeyDecodingFail()
    {
        return $this->wrappedResult->didPublicKeyDecodingFail();
    }
}
