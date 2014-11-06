<?php

namespace Surfnet\StepupGateway\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder;

        $treeBuilder
            ->root('surfnet_stepup_gateway_api')
                ->children()
                    ->scalarNode('http_basic_realm')
                        ->defaultValue('Secure Gateway API')
                        ->validate()
                            ->ifTrue(function ($realm) {
                                return !is_string($realm) || empty($realm);
                            })
                            ->thenInvalid("Invalid HTTP Basic realm '%s'. Must be string and non-empty.")
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
