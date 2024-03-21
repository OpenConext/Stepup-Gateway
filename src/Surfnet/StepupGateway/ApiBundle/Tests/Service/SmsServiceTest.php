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
 * Integration test for Sms services (spryng)
 */
final class SmsServiceTest extends TestCase
{
    private SpryngService $spryng;

    public function setUp(): void
    {
        $logger = m::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();
        $this->spryng = new SpryngService('apikey', '', $logger);
        // Messy business building a MessageBird test setup
    }

    public function test_spryng_integration_happy_flow(): void
    {
        $adapter = new SmsAdapterProvider('spryng');
        $adapter->addSmsAdapter($this->spryng);
        $service = new SmsService($adapter);
        $result = $service->send($this->getMessage());
        self::assertInstanceOf(SmsMessageResultInterface::class, $result);
        self::assertInstanceOf(SpryngMessageResult::class, $result);

        // We are not testing the spryng client here. It is unable to connect & send the message
        self::assertFalse($result->isSuccess());
    }

    private function getMessage(): SmsMessage
    {
        $smsMessage = new SmsMessage();
        $smsMessage->recipient = '0634784029';
        $smsMessage->originator = '0612121212';
        $smsMessage->body = 'Reminder from Jani. Dont forget me this weekend!';
        return $smsMessage;
    }

}
