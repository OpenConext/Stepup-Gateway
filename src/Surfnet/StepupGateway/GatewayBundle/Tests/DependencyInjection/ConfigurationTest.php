<?php

/**
 * Copyright 2015 SURFnet B.V.
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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\GatewayBundle\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    public const USE_REGEXP = true;

    /**
     * @test
     * @group configuration
     */
    public function it_requires_intrinsic_loa_to_be_configured(): void
    {
        $this->assertConfigurationIsInvalid([[]], '~intrinsic_loa.+must be configured~', self::USE_REGEXP);
    }

    /**
     * @test
     * @group configuration
     */
    public function it_requires_second_factors_to_be_configured(): void
    {
        $this->assertPartialConfigurationIsInvalid([[]], 'enabled_second_factors', '~enabled_second_factors.+must be configured~', self::USE_REGEXP);
    }

    /**
     * @test
     * @group configuration
     */
    public function it_allows_one_enabled_second_factor(): void
    {
        $this->assertConfigurationIsValid([['enabled_second_factors' => ['sms']]], 'enabled_second_factors');
    }

    /**
     * @test
     * @group configuration
     */
    public function it_allows_two_enabled_second_factors(): void
    {
        $this->assertConfigurationIsValid([['enabled_second_factors' => ['sms', 'yubikey']]], 'enabled_second_factors');
    }

    protected function getConfiguration(): \Surfnet\StepupGateway\GatewayBundle\DependencyInjection\Configuration
    {
        return new Configuration();
    }
}
