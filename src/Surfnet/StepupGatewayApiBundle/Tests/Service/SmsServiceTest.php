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

namespace Surfnet\StepupGatewayApiBundle\Tests\Service;

use Mockery as m;
use Surfnet\StepupGatewayApiBundle\Command\SendSmsCommand;
use Surfnet\StepupGatewayApiBundle\Service\SmsService;

class SmsServiceTest extends \PHPUnit_Framework_TestCase
{
    const MESSAGING_SERVICE = 'Surfnet\MessageBirdApiClientBundle\Service\MessagingService';
    const MESSAGE = 'Surfnet\MessageBirdApiClient\Messaging\Message';
    const SEND_MESSAGE_RESULT = 'Surfnet\MessageBirdApiClient\Messaging\SendMessageResult';

    public function testItSendsMessages()
    {
        $result = m::mock(self::SEND_MESSAGE_RESULT);

        $service = new SmsService(
            m::mock(self::MESSAGING_SERVICE)
                ->shouldReceive('send')->with(m::type(self::MESSAGE))->andReturn($result)
                ->getMock()
        );

        $command = new SendSmsCommand();
        $command->originator = 'SURFnet bv';
        $command->recipient = '31612345678';
        $command->body = 'Lorem ipsum dolor sit amet.';

        $this->assertEquals($result, $service->send($command));
    }
}
