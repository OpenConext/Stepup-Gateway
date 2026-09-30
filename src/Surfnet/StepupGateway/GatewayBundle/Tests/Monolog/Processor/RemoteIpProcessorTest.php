<?php

/**
 * Copyright 2026 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Monolog\Processor;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\GatewayBundle\Monolog\Processor\RemoteIpProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RemoteIpProcessorTest extends TestCase
{
    private function createLogRecord(): LogRecord
    {
        return new LogRecord(
            new DateTimeImmutable(),
            'app',
            Level::Info,
            'Test log message',
            [],
            ['request_id' => '12345']
        );
    }

    public function test_does_nothing_when_no_request_present(): void
    {
        $requestStack = new RequestStack();
        $processor = new RemoteIpProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertSame($record, $processed);
        $this->assertArrayNotHasKey('remote_ip', $processed->extra);
    }

    public function test_adds_remote_ip_from_client_ip(): void
    {
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '192.0.2.1']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $processor = new RemoteIpProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayHasKey('remote_ip', $processed->extra);
        $this->assertSame('192.0.2.1', $processed->extra['remote_ip']);
        $this->assertSame('12345', $processed->extra['request_id']);
    }

    public function test_does_not_add_remote_ip_when_client_ip_is_null(): void
    {
        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $processor = new RemoteIpProcessor($requestStack);

        $record = $this->createLogRecord();
        $processed = $processor($record);

        $this->assertArrayNotHasKey('remote_ip', $processed->extra);
    }
}
