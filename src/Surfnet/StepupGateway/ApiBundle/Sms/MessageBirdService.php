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

namespace Surfnet\StepupGateway\ApiBundle\Service\Sms;

use Psr\Log\LoggerInterface;
use Surfnet\MessageBirdApiClient\Messaging\Message;
use Surfnet\MessageBirdApiClientBundle\Service\MessagingService;
use Surfnet\StepupGateway\ApiBundle\Dto\SmsMessage;

class MessageBirdService implements SmsAdapterInterface
{
    /**
     * @var MessagingService
     */
    private $messagingService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(MessagingService $messagingService, LoggerInterface $logger)
    {
        $this->messagingService = $messagingService;
        $this->logger = $logger;
    }

    public function send(SmsMessage $message): SmsMessageResultInterface
    {
        $this->logger->notice('Using MessageBird to send an SMS');

        $message = new Message($message->originator, $message->recipient, $message->body);
        $result = $this->messagingService->send($message);

        if (!$result->isSuccess()) {
            $this->logger->warning('Sending OTP per SMS failed.');
        }

        return $result;
    }
}
