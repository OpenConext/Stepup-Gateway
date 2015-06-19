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

namespace Surfnet\StepupGateway\GatewayBundle\Service\StepUp;

use Surfnet\StepupBundle\Value\YubikeyPublicId;

class YubikeyOtpVerificationResult
{
    const RESULT_PUBLIC_ID_MATCHED = 0;
    const RESULT_PUBLIC_ID_DID_NOT_MATCH = 1;
    const RESULT_OTP_VERIFICATION_FAILED = 2;

    /**
     * @var \Surfnet\StepupBundle\Value\YubikeyPublicId|null
     */
    private $publicId;

    /**
     * @var int One of the RESULT constants.
     */
    private $result;

    /**
     * @param int $result
     * @param YubikeyPublicId|null $publicId
     * @throws DomainException When $result is not one of the RESULT constants.
     * @throws InvalidArgumentException When the public ID is not a string.
     */
    public function __construct($result, YubikeyPublicId $publicId = null)
    {
        $acceptableResults = [
            self::RESULT_PUBLIC_ID_MATCHED,
            self::RESULT_PUBLIC_ID_DID_NOT_MATCH,
            self::RESULT_OTP_VERIFICATION_FAILED
        ];

        if (!in_array($result, $acceptableResults)) {
            throw new DomainException('Public ID verification result is not one of the RESULT constants.');
        }

        $this->result = $result;
        $this->publicId = $publicId;
    }

    public function didPublicIdMatch()
    {
        return $this->result === self::RESULT_PUBLIC_ID_MATCHED && $this->publicId !== null;
    }

    public function didOtpVerificationFail()
    {
        return $this->result === self::RESULT_OTP_VERIFICATION_FAILED;
    }

    /**
     * @return YubikeyPublicId|null
     */
    public function getPublicId()
    {
        return $this->publicId;
    }
}
