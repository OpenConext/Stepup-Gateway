<?php

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\AllowedServiceProviders;

class AllowedServiceProvidersTest extends TestCase
{
    public function test_allowed_gssps_can_be_tested()
    {
        $allowedProviders = new AllowedServiceProviders(
            ['https://ra.stepup.example.com/vetting/gssf/tiqr/metadata'],
            '/^https:\/\/ra.tld\/vetting-procedure\/gssf\/[0-9.A-_\-Za-z]+\/metadata$/'
        );
        self::assertTrue($allowedProviders->isConfigured('https://ra.stepup.example.com/vetting/gssf/tiqr/metadata'));
        self::assertFalse($allowedProviders->isConfigured('https://not-configured/foboar/metadata'));
    }
}
