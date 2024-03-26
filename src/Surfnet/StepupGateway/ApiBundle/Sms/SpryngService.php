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

use Psr\Log\LoggerInterface;
use Spryng\SpryngRestApi\Objects\Message;
use Spryng\SpryngRestApi\Resources\MessageClient;
use Spryng\SpryngRestApi\Spryng;
use Surfnet\StepupGateway\ApiBundle\Dto\SmsMessage;

class SpryngService implements SmsAdapterInterface
{
    private readonly MessageClient $client;

    public function __construct(
        string $apiKey, /**
         * @var string
         */
        private readonly ?string $route,
        private readonly LoggerInterface $logger,
    ) {
        $this->client = new MessageClient(new Spryng($apiKey));
    }

    public function send(SmsMessage $message): SmsMessageResultInterface
    {
        $this->logger->notice('Using Spryng to send an SMS');

        $spryngMessage = new Message();
        $spryngMessage->setBody($message->body);
        $spryngMessage->setRecipients([$message->recipient]);
        $spryngMessage->setOriginator($message->originator);

        if (!is_null($this->route)) {
            $spryngMessage->setRoute($this->route);
        }

        $response = $this->client->create($spryngMessage);
        if (is_null($response) || !$response->wasSuccessful()) {
            $this->logger->warning('Sending OTP per SMS failed.');
        }
        return new SpryngMessageResult($response);
    }
}
