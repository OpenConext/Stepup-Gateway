<?php

/**
 * Copyright 2021 SURFnet B.V.
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

use Spryng\SpryngRestApi\Http\Response;

class SpryngMessageResult implements SmsMessageResultInterface
{
    private $message;

    public function __construct(?Response $message)
    {
        $this->message = $message;
    }

    public function isSuccess(): bool
    {
        if (!$this->message) {
            return false;
        }
        return $this->message->wasSuccessful();
    }

    public function isMessageInvalid(): bool
    {
        if (!$this->message) {
            return false;
        }
        return $this->message->serverError();
    }

    public function getRawErrors(): array
    {
        if (!$this->message) {
            return [];
        }
        $error = json_decode($this->message->getRawBody(), 1);
        return [
            'description' => $error['message'],
            'code' => $this->message->getResponseCode(),
        ];
    }
}
