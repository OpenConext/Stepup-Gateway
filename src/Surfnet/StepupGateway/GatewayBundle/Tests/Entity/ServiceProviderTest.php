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

namespace Surfnet\StepupGateway\GatewayBundle\Test\Entity;

use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;

class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_allows_second_factor_only_sps()
    {
        $sp = new ServiceProvider(['secondFactorOnly' => true]);
        $this->assertTrue($sp->mayUseSecondFactorOnly());
    }

    /**
     * @test
     */
    public function it_disallows_second_factor_only_sps_for_gateway()
    {
        $sp = new ServiceProvider(['secondFactorOnly' => true]);
        $this->assertFalse($sp->mayUseGateway());
    }

    /**
     * @test
     */
    public function it_allows_gateway_sps()
    {
        $sp = new ServiceProvider(['secondFactorOnly' => false]);
        $this->assertTrue($sp->mayUseGateway());
    }

    /**
     * @test
     */
    public function it_disallows_second_factor_only_sps()
    {
        $sp = new ServiceProvider(['secondFactorOnly' => false]);
        $this->assertFalse($sp->mayUseSecondFactorOnly());
    }

    /**
     * @test
     */
    public function it_blocks_disallowed_nameids()
    {
        $sp = new ServiceProvider([
            'secondFactorOnly' => true,
            'secondFactorOnlyNameIdPatterns' => [],
            ]
        );
        $this->assertFalse($sp->isAllowedToUseSecondFactorOnlyFor('urn:collab:person:ibuildings.nl:boy'));
        $this->assertFalse($sp->isAllowedToUseSecondFactorOnlyFor('urn:collab:person:surfnet.nl:pieter'));
    }

    /**
     * @test
     */
    public function it_allows_whitelisted_nameids()
    {
        $sp = new ServiceProvider([
                'secondFactorOnly' => true,
                'secondFactorOnlyNameIdPatterns' => [
                    'urn:collab:person:ibuildings.nl:*',
                    'urn:collab:person:surfnet.nl:*',
                ],
            ]
        );
        $this->assertTrue($sp->isAllowedToUseSecondFactorOnlyFor('urn:collab:person:ibuildings.nl:boy'));
        $this->assertTrue($sp->isAllowedToUseSecondFactorOnlyFor('urn:collab:person:surfnet.nl:pieter'));

        $sp = new ServiceProvider([
                'secondFactorOnly' => true,
                'secondFactorOnlyNameIdPatterns' => [
                    'urn:collab:person:*',
                ],
            ]
        );
        $this->assertTrue($sp->isAllowedToUseSecondFactorOnlyFor('urn:collab:person:ibuildings.nl:boy'));
        $this->assertTrue($sp->isAllowedToUseSecondFactorOnlyFor('urn:collab:person:surfnet.nl:pieter'));

        $sp = new ServiceProvider([
                'secondFactorOnly' => true,
                'secondFactorOnlyNameIdPatterns' => [
                    '*',
                ],
            ]
        );
        $this->assertTrue($sp->isAllowedToUseSecondFactorOnlyFor('urn:collab:person:ibuildings.nl:boy'));
        $this->assertTrue($sp->isAllowedToUseSecondFactorOnlyFor('urn:collab:person:surfnet.nl:pieter'));
    }
}
