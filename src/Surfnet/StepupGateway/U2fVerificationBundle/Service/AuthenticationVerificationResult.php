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

use Surfnet\StepupU2fBundle\Service\AuthenticationVerificationResult as U2fAuthenticationVerificationResult;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
final class AuthenticationVerificationResult
{
    /**
     * @var \Surfnet\StepupU2fBundle\Service\AuthenticationVerificationResult
     */
    private $wrappedResult;

    /**
     * @var bool
     */
    private $registrationUnknown = false;

    /**
     * @param U2fAuthenticationVerificationResult $u2fResult
     * @return self
     */
    public static function wrap(U2fAuthenticationVerificationResult $u2fResult)
    {
        $result = new self;
        $result->wrappedResult = $u2fResult;

        return $result;
    }

    /**
     * @return self
     */
    public static function registrationUnknown()
    {
        $result = new self;
        $result->registrationUnknown = true;

        return $result;
    }

    /**
     * @return bool
     */
    public function wasSuccessful()
    {
        return $this->wrappedResult && $this->wrappedResult->wasSuccessful();
    }

    /**
     * @return bool
     */
    public function wasRegistrationUnknown()
    {
        return $this->registrationUnknown;
    }

    /**
     * @return bool
     */
    public function didDeviceReportABadRequest()
    {
        return $this->wrappedResult && $this->wrappedResult->didDeviceReportABadRequest();
    }

    /**
     * @return bool
     */
    public function wasClientConfigurationUnsupported()
    {
        return $this->wrappedResult && $this->wrappedResult->wasClientConfigurationUnsupported();
    }

    /**
     * @return bool
     */
    public function wasKeyHandleUnknownToDevice()
    {
        return $this->wrappedResult && $this->wrappedResult->wasKeyHandleUnknownToDevice();
    }

    /**
     * @return bool
     */
    public function didDeviceTimeOut()
    {
        return $this->wrappedResult && $this->wrappedResult->didDeviceTimeOut();
    }

    /**
     * @return bool
     */
    public function didDeviceReportAnUnknownError()
    {
        return $this->wrappedResult && $this->wrappedResult->didDeviceReportAnUnknownError();
    }

    /**
     * @return bool
     */
    public function didDeviceReportAnyError()
    {
        return $this->wrappedResult && $this->wrappedResult->didDeviceReportAnyError();
    }

    /**
     * @return bool
     */
    public function didResponseChallengeNotMatchRequestChallenge()
    {
        return $this->wrappedResult && $this->wrappedResult->didResponseChallengeNotMatchRequestChallenge();
    }

    /**
     * @return bool
     */
    public function didResponseChallengeNotMatchRegistration()
    {
        return $this->wrappedResult && $this->wrappedResult->didResponseChallengeNotMatchRegistration();
    }

    /**
     * @return bool
     */
    public function wasResponseNotSignedByDevice()
    {
        return $this->wrappedResult && $this->wrappedResult->wasResponseNotSignedByDevice();
    }

    /**
     * @return bool
     */
    public function didPublicKeyDecodingFail()
    {
        return $this->wrappedResult && $this->wrappedResult->didPublicKeyDecodingFail();
    }
}
