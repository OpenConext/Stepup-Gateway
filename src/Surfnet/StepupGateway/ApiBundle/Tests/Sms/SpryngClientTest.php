<?php

/**
 * Copyright 2026 SURFnet B.V.
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

namespace Surfnet\StepupGateway\ApiBundle\Tests\Sms;

use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\ApiBundle\Sms\SpryngClient;

final class SpryngClientTest extends TestCase
{
    public function test_default_base_url_is_used_when_no_base_url_is_given(): void
    {
        self::assertSame(SpryngClient::DEFAULT_BASE_URL, (new SpryngClient('apikey'))->getBaseUrl());
        self::assertSame(SpryngClient::DEFAULT_BASE_URL, (new SpryngClient('apikey', null))->getBaseUrl());
        self::assertSame(SpryngClient::DEFAULT_BASE_URL, (new SpryngClient('apikey', ''))->getBaseUrl());
    }

    public function test_base_url_can_be_configured(): void
    {
        $client = new SpryngClient('apikey', 'https://smscatcher.dev.openconext.local/v1');

        self::assertSame('https://smscatcher.dev.openconext.local/v1', $client->getBaseUrl());
    }

    public function test_trailing_slash_is_removed_from_configured_base_url(): void
    {
        $client = new SpryngClient('apikey', 'https://smscatcher.dev.openconext.local/v1/');

        self::assertSame('https://smscatcher.dev.openconext.local/v1', $client->getBaseUrl());
    }
}
