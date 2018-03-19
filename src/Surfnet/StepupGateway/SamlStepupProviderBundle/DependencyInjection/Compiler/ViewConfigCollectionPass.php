<?php

/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\DependencyInjection\Compiler;

use Surfnet\StepupGateway\SamlStepupProviderBundle\DependencyInjection\SurfnetStepupGatewaySamlStepupProviderExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ViewConfigCollectionPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('surfnet_stepup.provider.collection')) {
            return;
        }

        $definition = $container->findDefinition('surfnet_stepup.provider.collection');
        $taggedServices = $container->findTaggedServiceIds(
            SurfnetStepupGatewaySamlStepupProviderExtension::VIEW_CONFIG_TAG_NAME
        );

        $taggedServices = array_keys($taggedServices);

        foreach ($taggedServices as $id) {
            preg_match('/^gssp\.view_config\.(\w+)$/', $id, $gsspIdMatches);
            if (!is_array($gsspIdMatches)) {
                throw new InvalidConfigurationException('A manually tagged view config service was named incorrectly.');
            }
            $definition->addMethodCall('addViewConfig', [new Reference($id), end($gsspIdMatches)]);
        }
    }
}
