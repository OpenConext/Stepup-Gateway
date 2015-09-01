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

namespace Surfnet\StepupGateway\U2fVerificationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder;

        $treeBuilder
            ->root('surfnet_stepup_gateway_u2f_verification')
            ->children()
                ->arrayNode('migrations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('diff_entity_manager')
                            ->info(
                                'This is the name of the Doctrine entity manager that is used for schema ' .
                                'diffing. If it is unspecified, or NULL, the default entity manager is used.'
                            )
                            ->defaultNull()
                            ->validate()
                                ->ifTrue(
                                    function ($entityManagerName) {
                                        return !is_string($entityManagerName) && $entityManagerName !== null;
                                    }
                                )
                                ->thenInvalid(
                                    'surfnet_stepup_gateway_u2f_verification.migrations.diff_entity_manager should ' .
                                    'be a string or NULL'
                                )
                            ->end()
                        ->end()
                        ->scalarNode('migrate_entity_manager')
                            ->info(
                                'This is the name of the Doctrine entity manager that is used for migrations. ' .
                                'If it is unspecified, or NULL, the default entity manager is used.'
                            )
                            ->defaultNull()
                            ->validate()
                                ->ifTrue(
                                    function ($entityManagerName) {
                                        return !is_string($entityManagerName) && $entityManagerName !== null;
                                    }
                                )
                                ->thenInvalid(
                                    'surfnet_stepup_gateway_u2f_verification.migrations.migrate_entity_manager ' .
                                    'should be a string or NULL'
                                )
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
