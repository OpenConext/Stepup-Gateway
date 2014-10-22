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

namespace Surfnet\StepupBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @codeCoverageIgnore
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder;

        $treeBuilder
            ->root('surfnet_bundle')
                ->children()
                    ->arrayNode('request_id')
                        ->children()
                            ->scalarNode('header')
                                ->defaultValue('X-Stepup-Request-Id')
                                ->validate()
                                    ->ifTrue(function ($headerName) {
                                        return !is_string($headerName) || empty($headerName);
                                    })
                                    ->thenInvalid(
                                        "Request ID header name must be non-empty string, eg. X-Stepup-Request-Id"
                                    )
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
