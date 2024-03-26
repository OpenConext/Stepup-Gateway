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

use Surfnet\StepupGateway\ApiBundle\Dto\SmsMessage;
use Surfnet\StepupGateway\ApiBundle\Sms\SmsAdapterInterface;
use Surfnet\StepupGateway\ApiBundle\Sms\SmsAdapterProvider;
use Surfnet\StepupGateway\ApiBundle\Sms\SmsMessageResultInterface;

class SmsService implements SmsServiceInterface
{
    private readonly \Surfnet\StepupGateway\ApiBundle\Sms\SmsAdapterInterface $messagingService;

    public function __construct(SmsAdapterProvider $provider)
    {
        $this->messagingService = $provider->getSelectedService();
    }

    public function send(SmsMessage $message): SmsMessageResultInterface
    {
        return $this->messagingService->send($message);
    }
}
