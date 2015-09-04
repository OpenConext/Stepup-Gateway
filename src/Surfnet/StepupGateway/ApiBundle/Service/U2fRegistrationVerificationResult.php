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

namespace Surfnet\StepupGateway\ApiBundle\Service;

use JsonSerializable;

final class U2fRegistrationVerificationResult implements JsonSerializable
{
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_UNMATCHED_REGISTRATION_CHALLENGE = 'UNMATCHED_REGISTRATION';
    const STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE = 'RESPONSE_NOT';
    const STATUS_UNTRUSTED_DEVICE = 'UNTRUSTED_DEVICE';
    const STATUS_PUBLIC_KEY_DECODING_FAILED = 'PUBLIC_KEY';
    const STATUS_DEVICE_ERROR = 'DEVICE_ERROR';

    /**
     * @var string
     */
    public $status;

    /**
     * @var string
     */
    public $keyHandle;

    public function jsonSerialize()
    {
        return [
            'status'     => $this->status,
            'key_handle' => $this->keyHandle,
        ];
    }
}
