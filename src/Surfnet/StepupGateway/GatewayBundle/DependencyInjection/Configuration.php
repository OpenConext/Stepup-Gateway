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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('surfnet_stepup_gateway_gateway');

        $rootNode = $treeBuilder->getRootNode();

        $children = $rootNode->children();
        $children
            ->scalarNode('intrinsic_loa')
                ->isRequired()
            ->end()
            ->arrayNode('sso_on_second_factor')
                ->isRequired()
                ->children()
                    ->enumNode('cookie_type')
                        ->values(['session', 'persistent'])
                        ->isRequired()
                    ->end()
                    ->scalarNode('cookie_name')
                        ->isRequired()
                    ->end()
                    ->integerNode('cookie_lifetime')
                        ->isRequired()
                    ->end()
                    ->scalarNode('encryption_key')
                        ->isRequired()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('enabled_second_factors')
                ->isRequired()
                ->prototype('scalar')
            ->end();
        $children
            ->arrayNode('enabled_generic_second_factors')
                ->isRequired()
                ->prototype('array')
                ->children()
                    ->scalarNode('loa')
                    ->isRequired()
                    ->info('The lao level of the Gssf')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
