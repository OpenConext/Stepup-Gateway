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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('surfnet_stepup_gateway_saml_stepup_provider');

        $rootNode
            ->children()
            ->arrayNode('allowed_sps')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('scalar')
                ->end()
            ->end();

        $this->addRoutesSection($rootNode);
        $this->addProvidersSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addRoutesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
            ->arrayNode('routes')
                ->children()
                    ->scalarNode('sso')
                        ->isRequired()
                        ->validate()
                            ->ifTrue(function ($v) {
                                return !is_string($v) || strlen($v) === 0;
                            })
                            ->thenInvalid('SSO route must be a non-empty string')
                        ->end()
                    ->end()
                    ->scalarNode('consume_assertion')
                        ->isRequired()
                        ->validate()
                            ->ifTrue(function ($v) {
                                return !is_string($v) || strlen($v) === 0;
                            })
                            ->thenInvalid('Consume assertion route must be a non-empty string')
                        ->end()
                    ->end()
                    ->scalarNode('metadata')
                        ->isRequired()
                        ->validate()
                            ->ifTrue(function ($v) {
                                return !is_string($v) || strlen($v) === 0;
                            })
                            ->thenInvalid('Metadata route must be a non-empty string')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addProvidersSection(ArrayNodeDefinition $rootNode)
    {
        /** @var ArrayNodeDefinition $protoType */
        $protoType = $rootNode
            ->children()
            ->arrayNode('providers')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('type')
                ->prototype('array');

        $protoType
            ->canBeDisabled()
            ->children()
                ->arrayNode('hosted')
                    ->children()
                        ->arrayNode('service_provider')
                            ->children()
                                ->scalarNode('public_key')
                                    ->isRequired()
                                    ->info('The absolute path to the public key used to sign AuthnRequests')
                                ->end()
                                ->scalarNode('private_key')
                                    ->isRequired()
                                    ->info('The absolute path to the private key used to sign AuthnRequests')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('identity_provider')
                            ->children()
                                ->scalarNode('service_provider_repository')
                                    ->isRequired()
                                    ->info(
                                        'Name of the service that is the Entity Repository. Must implement the '
                                        . ' Surfnet\SamlBundle\Entity\ServiceProviderRepository interface.'
                                    )
                                ->end()
                                ->scalarNode('public_key')
                                    ->isRequired()
                                    ->info('The absolute path to the public key used to sign Responses to AuthRequests with')
                                ->end()
                                ->scalarNode('private_key')
                                    ->isRequired()
                                    ->info('The absolute path to the private key used to sign Responses to AuthRequests with')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('metadata')
                            ->children()
                                ->scalarNode('public_key')
                                    ->isRequired()
                                    ->info('The absolute path to the public key used to sign the metadata')
                                ->end()
                                ->scalarNode('private_key')
                                    ->isRequired()
                                    ->info('The absolute path to the private key used to sign the metadata')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('remote')
                    ->children()
                        ->scalarNode('entity_id')
                            ->isRequired()
                            ->info('The EntityID of the remote identity provider')
                        ->end()
                        ->scalarNode('sso_url')
                            ->isRequired()
                            ->info('The name of the route to generate the SSO URL')
                        ->end()
                        ->scalarNode('certificate')
                            ->isRequired()
                            ->info(
                                'The contents of the certificate used to sign the AuthnResponse with, if different from'
                                . ' the public key configured below'
                            )
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
