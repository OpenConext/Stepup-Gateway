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

namespace Surfnet\StepupGateway\ApiBundle\Tests\Service;

use GuzzleHttp\Client;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Surfnet\MessageBirdApiClientBundle\Service\MessagingService as BundleMessagingService;
use Surfnet\MessageBirdApiClient\Messaging\MessagingService as LibraryMessagingService;
use Surfnet\StepupGateway\ApiBundle\Dto\SmsMessage;
use Surfnet\StepupGateway\ApiBundle\Sms\MessageBirdMessageResult;
use Surfnet\StepupGateway\ApiBundle\Sms\MessageBirdService;
use Surfnet\StepupGateway\ApiBundle\Sms\SmsAdapterProvider;
use Surfnet\StepupGateway\ApiBundle\Sms\SmsMessageResultInterface;
use Surfnet\StepupGateway\ApiBundle\Sms\SpryngMessageResult;
use Surfnet\StepupGateway\ApiBundle\Sms\SpryngService;
use Surfnet\StepupGateway\ApiBundle\Service\SmsService;

/**
 * Integration test for Sms services (spryng and messagebird)
 */
final class SmsServiceTest extends TestCase
{
    private $logger;
    private $spryng;
    private $messageBird;
    private $adapter;

    public function setUp(): void
    {
        $this->logger = m::mock(LoggerInterface::class);
        $this->logger->shouldIgnoreMissing();
        $this->spryng = new SpryngService('apikey', '', $this->logger);
        // Messy business building a MessageBird test setup
        $this->setUpMessageBird($this->logger);
    }

    public function test_spryng_integration_happy_flow(): void
    {
        $this->adapter = new SmsAdapterProvider('spryng');
        $this->adapter->addSmsAdapter($this->spryng);
        $this->adapter->addSmsAdapter($this->messageBird);
        $service = new SmsService($this->adapter);
        $result = $service->send($this->getMessage());
        self::assertInstanceOf(SmsMessageResultInterface::class, $result);
        self::assertInstanceOf(SpryngMessageResult::class, $result);
        // We are not testing the spryng client here. It is unable to connect & send the message
        self::assertFalse($result->isSuccess());
    }

    public function test_message_bird_integration_happy_flow(): void
    {
        $this->adapter = new SmsAdapterProvider('messagebird');
        $this->adapter->addSmsAdapter($this->spryng);
        $this->adapter->addSmsAdapter($this->messageBird);
        $service = new SmsService($this->adapter);
        $result = $service->send($this->getMessage());
        self::assertInstanceOf(SmsMessageResultInterface::class, $result);
        self::assertInstanceOf(MessageBirdMessageResult::class, $result);
        // We are not testing the MB client here. It is unable to connect & send the message
        self::assertTrue($result->isSuccess());
    }

    private function getMessage(): SmsMessage
    {
        $smsMessage = new SmsMessage();
        $smsMessage->recipient = '0634784029';
        $smsMessage->originator = '0612121212';
        $smsMessage->body = 'Reminder from Jani. Dont forget me this weekend!';
        return $smsMessage;
    }

    private function setUpMessageBird(LoggerInterface $logger): void
    {
        $apiClient = m::mock(Client::class);
        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody')->andReturn(
            '{
              "id":"e8077d803532c0b5937c639b60216938",
              "href":"https://rest.messagebird.com/messages/e8077d803532c0b5937c639b60216938",
              "direction":"mt",
              "type":"sms",
              "originator":"YourName",
              "body":"This is a test message",
              "reference":null,
              "validity":null,
              "gateway":null,
              "typeDetails":{},
              "datacoding":"plain",
              "mclass":1,
              "scheduledDatetime":null,
              "createdDatetime":"2016-05-03T14:26:57+00:00",
              "recipients":{
                "totalCount":1,
                "totalSentCount":1,
                "totalDeliveredCount":0,
                "totalDeliveryFailedCount":0,
                "items":[
                  {
                    "recipient":31612345678,
                    "status":"sent",
                    "statusDatetime":"2016-05-03T14:26:57+00:00"
                  }
                ]
              }
            }'
        );
        $apiClient->shouldReceive('post')->andReturn($response);
        $this->messageBird = new MessageBirdService(
            new BundleMessagingService(
                new LibraryMessagingService($apiClient),
                $logger
            ),
            $logger
        );
    }
}
