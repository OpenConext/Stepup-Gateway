<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Service;

use PHPUnit_Framework_TestCase;
use Surfnet\StepupGateway\GatewayBundle\Service\InstitutionMatchingHelper;

final class InstitutionMatchingHelperTest extends PHPUnit_Framework_TestCase
{
    public function testFindMatches()
    {
        $helper = new InstitutionMatchingHelper();
        $matches = $helper->findMatches(
            ['institution.foobar.com', 'example.com'],
            ['example.com']
        );

        $this->assertEquals(['example.com'], $matches);
    }

    public function testFindMatchesCaseInsensitive()
    {
        $helper = new InstitutionMatchingHelper();
        $matches = $helper->findMatches(
            ['institution.foobar.com', 'example.com'],
            ['eXample.com']
        );

        $this->assertEquals(['example.com'], $matches);
    }

    public function testFindMatchesOfParameter1()
    {
        $helper = new InstitutionMatchingHelper();
        $matches = $helper->findMatches(
            ['institution.foobar.com', 'example.COM'],
            ['eXample.com']
        );

        $this->assertEquals(['example.COM'], $matches);
    }

}
