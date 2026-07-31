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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Surfnet\StepupGateway\GatewayBundle\Entity\SamlEntity;

class SamlEntityTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_maps_service_name_locale_map_from_configuration(): void
    {
        $samlEntity = $this->createSamlEntity(SamlEntity::TYPE_SP, [
            'acs' => ['https://sp.example.com/acs'],
            'public_key' => 'test-key',
            'loa' => ['__default__' => 'loa1'],
            'service_name' => ['en_GB' => 'Test Service', 'nl_NL' => 'Test Dienst'],
        ]);

        $serviceProvider = $samlEntity->toServiceProvider();

        $this->assertSame(
            ['en_GB' => 'Test Service', 'nl_NL' => 'Test Dienst'],
            $serviceProvider->getServiceNames()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_ignores_a_service_name_that_is_not_a_locale_map(): void
    {
        // Middleware's own validator (Stepup-Middleware#606) only enforces "nullable
        // string" for service_name, so a plain string can arrive here in practice.
        $samlEntity = $this->createSamlEntity(SamlEntity::TYPE_SP, [
            'acs' => ['https://sp.example.com/acs'],
            'public_key' => 'test-key',
            'loa' => ['__default__' => 'loa1'],
            'service_name' => 'Not a locale map',
        ]);

        $serviceProvider = $samlEntity->toServiceProvider();

        $this->assertSame([], $serviceProvider->getServiceNames());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_defaults_to_an_empty_service_names_map_when_absent(): void
    {
        $samlEntity = $this->createSamlEntity(SamlEntity::TYPE_SP, [
            'acs' => ['https://sp.example.com/acs'],
            'public_key' => 'test-key',
            'loa' => ['__default__' => 'loa1'],
        ]);

        $serviceProvider = $samlEntity->toServiceProvider();

        $this->assertSame([], $serviceProvider->getServiceNames());
    }

    /**
     * SamlEntity is a Doctrine entity without a public constructor or setters
     * (it is hydrated by Doctrine directly). Use reflection to build one for
     * unit testing purposes.
     *
     * @param array<string, mixed> $configuration
     */
    private function createSamlEntity(string $type, array $configuration): SamlEntity
    {
        $reflection = new ReflectionClass(SamlEntity::class);
        $samlEntity = $reflection->newInstanceWithoutConstructor();

        $entityIdProperty = $reflection->getProperty('entityId');
        $entityIdProperty->setAccessible(true);
        $entityIdProperty->setValue($samlEntity, 'https://sp.example.com');

        $typeProperty = $reflection->getProperty('type');
        $typeProperty->setAccessible(true);
        $typeProperty->setValue($samlEntity, $type);

        $configurationProperty = $reflection->getProperty('configuration');
        $configurationProperty->setAccessible(true);
        $configurationProperty->setValue($samlEntity, json_encode($configuration));

        return $samlEntity;
    }
}
