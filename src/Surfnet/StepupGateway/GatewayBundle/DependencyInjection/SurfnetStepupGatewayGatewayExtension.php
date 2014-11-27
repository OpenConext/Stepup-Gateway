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

namespace Surfnet\StepupGateway\GatewayBundle\DependencyInjection;

use Surfnet\StepupGateway\GatewayBundle\Value\Loa;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class SurfnetStepupGatewayGatewayExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('repositories.yml');

        $this->defineLoas($config['loa_definition'], $container);
    }

    private function defineLoas($loaDefinitions, ContainerBuilder $container)
    {
        if (!$container->hasDefinition('gateway.service.loa_resolution')) {
            throw new InvalidConfigurationException("Required service gateway.service.loa_resolution does not exist");
        }

        $loaService = $container->getDefinition('gateway.service.loa_resolution');

        $loa1 = new Definition('Surfnet\StepupGateway\GatewayBundle\Value\Loa', [Loa::LOA_1, $loaDefinitions['loa1']]);
        $loa2 = new Definition('Surfnet\StepupGateway\GatewayBundle\Value\Loa', [Loa::LOA_2, $loaDefinitions['loa2']]);
        $loa3 = new Definition('Surfnet\StepupGateway\GatewayBundle\Value\Loa', [Loa::LOA_3, $loaDefinitions['loa3']]);

        $loaService->addMethodCall('addLoa', [$loa1]);
        $loaService->addMethodCall('addLoa', [$loa2]);
        $loaService->addMethodCall('addLoa', [$loa3]);
        $loaService->addMethodCall('lock');
    }
}
