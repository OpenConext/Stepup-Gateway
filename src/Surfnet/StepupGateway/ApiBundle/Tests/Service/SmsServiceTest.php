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

use Mockery as m;
use Surfnet\StepupGateway\ApiBundle\Command\SendSmsCommand;
use Surfnet\StepupGateway\ApiBundle\Service\SmsService;

class SmsServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testItSendsMessages()
    {
        $result = m::mock('Surfnet\MessageBirdApiClient\Messaging\SendMessageResult')
            ->shouldReceive('isSuccess')->andReturn(true)
            ->getMock();

        $service = new SmsService(
            m::mock('Surfnet\MessageBirdApiClientBundle\Service\MessagingService')
                ->shouldReceive('send')
                    ->with(m::type('Surfnet\MessageBirdApiClient\Messaging\Message'))
                    ->andReturn($result)
                ->getMock(),
            m::mock('Psr\Log\LoggerInterface')->shouldIgnoreMissing()
        );

        $command = new SendSmsCommand();
        $command->originator = 'SURFnetbv';
        $command->recipient = '31612345678';
        $command->body = 'Lorem ipsum dolor sit amet.';

        $this->assertEquals($result, $service->send($command));
    }
}
