<?php

/**
 * Copyright 2015 SURFnet bv
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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\U2fVerificationBundle\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    /**
     * @test
     * @group u2f_verification
     * @group u2f_verification_bundle
     */
    public function migrations_entity_manager_defaults_to_null()
    {
        $this->assertProcessedConfigurationEquals(
            [],
            ['migrations' => ['diff_entity_manager' => null, 'migrate_entity_manager' => null]]
        );
    }

    /**
     * @test
     * @group u2f_verification
     * @group u2f_verification_bundle
     */
    public function migrations_entity_manager_may_be_string()
    {
        $this->assertProcessedConfigurationEquals(
            [['migrations' => ['diff_entity_manager' => 'deploy', 'migrate_entity_manager' => 'deploy']]],
            ['migrations' => ['diff_entity_manager' => 'deploy', 'migrate_entity_manager' => 'deploy']]
        );
    }

    /**
     * @test
     * @group u2f_verification
     * @group u2f_verification_bundle
     */
    public function migrations_entity_manager_may_be_null()
    {
        $this->assertProcessedConfigurationEquals(
            [['migrations' => ['diff_entity_manager' => null, 'migrate_entity_manager' => null]]],
            ['migrations' => ['diff_entity_manager' => null, 'migrate_entity_manager' => null]]
        );
    }

    /**
     * @test
     * @dataProvider nonStrings
     * @group u2f_verification
     * @group u2f_verification_bundle
     *
     * @param mixed $nonString
     */
    public function migrations_entity_manager_must_be_string($nonString)
    {
        $this->assertConfigurationIsInvalid(
            [['migrations' => ['diff_entity_manager' => $nonString, 'migrate_entity_manager' => null]]]
        );
        $this->assertConfigurationIsInvalid(
            [['migrations' => ['diff_entity_manager' => null, 'migrate_entity_manager' => $nonString]]]
        );
    }

    public function nonStrings()
    {
        return [
            'int'      => [1],
            'float'    => [1.1],
            'resource' => [fopen('php://memory', 'w')],
            'object'   => [new \stdClass],
            'array'    => [array()],
            'bool'     => [false],
        ];
    }

    protected function getConfiguration()
    {
        return new Configuration();
    }
}
