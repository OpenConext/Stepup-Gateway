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

namespace Surfnet\StepupGateway\GatewayBundle\Service;

use Surfnet\StepupBundle\Command\SendSmsCommand;
use Surfnet\StepupBundle\Service\SmsService;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Dto\SmsMessage;
use Surfnet\StepupGateway\ApiBundle\Service\SmsService as ApiSmsService;

/**
 * Sends SMSes by calling the SMS service in the API bundle.
 */
final readonly class GatewayApiSmsService implements SmsService
{
    public function __construct(private ApiSmsService $smsService)
    {
    }

    /**
     * @param SendSmsCommand $command
     * @return bool
     */
    public function sendSms(SendSmsCommand $command)
    {
        $message = new SmsMessage();
        $message->recipient = $command->recipient;
        $message->originator = $command->originator;
        $message->body = $command->body;

        return $this->smsService->send($message)->isSuccess();
    }
}
