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

use JsonSerializable;

final class U2fAuthenticationVerificationResult implements JsonSerializable
{
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_REQUEST_RESPONSE_MISMATCH = 'REQUEST_RESPONSE_MISMATCH';
    const STATUS_REGISTRATION_UNKNOWN = 'REGISTRATION_UNKNOWN';
    const STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE = 'RESPONSE_NOT_SIGNED_BY_DEVICE';
    const STATUS_PUBLIC_KEY_DECODING_FAILED = 'PUBLIC_KEY_DECODING_FAILED';
    const STATUS_DEVICE_ERROR = 'DEVICE_ERROR';

    /**
     * @var string
     */
    public $status;

    public function jsonSerialize()
    {
        return ['status' => $this->status];
    }
}
