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

namespace Surfnet\SamlBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class SurfnetSamlExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $this->compileMetadataObjects($config['metadata'], $container);
    }

    /**
     * Creates and registers MetadataConfiguration objects based on the configuration given.
     *
     * @param array $metadataConfigurations
     * @param ContainerBuilder $container
     */
    private function compileMetadataObjects(array $metadataConfigurations, ContainerBuilder $container)
    {
        foreach ($metadataConfigurations as $name => $configuration) {
            $metadata = new Definition('Surfnet\SamlBundle\Metadata\MetadataConfiguration');

            $serviceProvider = $configuration['service_provider'];
            $identityProvider = $configuration['identity_provider'];

            $metadata->setProperties([
                'name' => $name,
                'entityIdRoute' => $configuration['entity_id_route'],
                'isSp' => $serviceProvider['enabled'],
                'assertionConsumerRoute' => $serviceProvider['assertion_consumer_route'],
                'isIdP' => $identityProvider['enabled'],
                'ssoRoute' => $identityProvider['sso_route'],
                'idpCertificate' => $identityProvider['certificate'],
                'publicKey' => $configuration['public_key'],
                'privateKey' => $configuration['private_key']
            ]);

            $container->setDefinition('surfnet_saml.metadata.' . $name, $metadata);
        }
    }
}
