<?php

/**
 * Copyright 2016 SURFnet bv
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class SurfnetStepupGatewaySecondFactorOnlyExtension extends Extension
{
    public function load(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yml');

        $this->replaceLoaAliasConfig($config, $container);
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function replaceLoaAliasConfig(
        array $config,
        ContainerBuilder $container
    ): void {
        $loaAliasMapping = [];
        foreach ($config[0]['loa_aliases'] as $mapping) {
            if (isset($loaAliasMapping[$mapping['loa']])) {
                throw new InvalidConfigurationException(
                    'Duplicate loa identifiers in surfnet_stepup_gateway_gateway.loa_domains.gateway'
                );
            }

            $loaAliasMapping[$mapping['loa']] = $mapping['alias'];
        }
        $container
            ->getDefinition('second_factor_only.loa_alias_lookup')
            ->replaceArgument(0, $loaAliasMapping);
    }
}
