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

use Psr\Log\LoggerInterface;
use Surfnet\MessageBirdApiClient\Messaging\Message;
use Surfnet\MessageBirdApiClient\Messaging\SendMessageResult;
use Surfnet\MessageBirdApiClientBundle\Service\MessagingService;
use Surfnet\StepupGateway\ApiBundle\Command\SendSmsCommand;

class SmsService
{
    /**
     * @var MessagingService
     */
    private $messagingService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param MessagingService $messagingService
     * @param LoggerInterface $logger
     */
    public function __construct(MessagingService $messagingService, LoggerInterface $logger)
    {
        $this->messagingService = $messagingService;
        $this->logger = $logger;
    }

    /**
     * @param SendSmsCommand $command
     * @return SendMessageResult
     */
    public function send(SendSmsCommand $command)
    {
        $this->logger->notice('Sending OTP per SMS.');

        $message = new Message($command->originator, $command->recipient, $command->body);
        $result = $this->messagingService->send($message);

        if (!$result->isSuccess()) {
            $this->logger->warning('Sending OTP per SMS failed.');
        }

        return $result;
    }
}
