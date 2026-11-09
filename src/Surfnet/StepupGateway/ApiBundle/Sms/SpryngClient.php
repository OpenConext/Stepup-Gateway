<?php

/**
 * Copyright 2026 SURFnet B.V.
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

namespace Surfnet\StepupGateway\ApiBundle\Sms;

use Spryng\SpryngRestApi\Spryng;

/**
 * Spryng API client that allows the base URL of the API to be configured. The vendor client hardcodes the base URL
 * in a protected property without a setter, so it is overridden here. When no base URL is given, the default of the
 * vendor client (https://rest.spryngsms.com/v1) is kept.
 */
class SpryngClient extends Spryng
{
    public const DEFAULT_BASE_URL = 'https://rest.spryngsms.com/v1';

    public function __construct(?string $apiKey = null, ?string $baseUrl = null)
    {
        parent::__construct($apiKey);

        if ($baseUrl !== null && $baseUrl !== '') {
            $this->baseUrl = rtrim($baseUrl, '/');
        }
    }
}
