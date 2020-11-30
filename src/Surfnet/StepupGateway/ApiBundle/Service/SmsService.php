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
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Dto\SmsMessage;
use Spryng\SpryngRestApi\Objects\Message;
use Spryng\SpryngRestApi\Spryng;

class SmsService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param SmsMessage $message
     * @param Requester $requester
     * @return SendMessageResult
     */
    public function send(SmsMessage $message, Requester $requester)
    {
        $this->logger->notice('Sending OTP per SMS.');

        // TODO: get apikey from parameters file
        $spryng = new Spryng("dummy");

        $myMessage = new Message();
        $myMessage->setBody($message->body);
        $myMessage->setRecipients([$message->recipient,]);
        $myMessage->setOriginator($message->originator);

        $response = $spryng->message->send($message);

        if ($response->wasSuccessful()) {
            $message = $response->toObject();
            $this->logger->notice("Message with ID " . $message->getId() . " was send successfully!");
        } else if ($response->serverError()) {
            $this->logger->error("Message could not be send because of a server error...");
        } else {
            $this->logger->error("Message could not be send. Response code: " . $response->getResponseCode());
        }

        return $response;
    }
}
