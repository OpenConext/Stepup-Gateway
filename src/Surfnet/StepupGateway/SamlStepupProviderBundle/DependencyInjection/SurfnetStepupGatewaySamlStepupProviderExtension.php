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

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class SurfnetStepupGatewaySamlStepupProviderExtension extends Extension
{
    const VIEW_CONFIG_TAG_NAME = 'gssp.view_config';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $connectedServiceProviders = $container->getDefinition('gssp.connected_service_providers');
        $connectedServiceProviders->replaceArgument(1, $config['allowed_sps']);

        foreach ($config['providers'] as $provider => $providerConfiguration) {
            // may seem a bit strange, but this prevents casing issue when getting/setting/creating provider
            // service definitions etc.
            if ($provider !== strtolower($provider)) {
                throw new InvalidConfigurationException('The provider name must be completely lowercase');
            }

            $this->loadProviderConfiguration($provider, $providerConfiguration, $config['routes'], $container);
        }
    }

    private function loadProviderConfiguration(
        $provider,
        array $configuration,
        array $routes,
        ContainerBuilder $container
    ) {
        if ($container->has('gssp.provider.' . $provider)) {
            throw new InvalidConfigurationException(sprintf('Cannot create the same provider "%s" twice', $provider));
        }

        $this->createHostedDefinitions($provider, $configuration['hosted'], $routes, $container);
        $this->createMetadataDefinition($provider, $configuration['hosted'], $routes, $container);
        $this->createRemoteDefinition($provider, $configuration['remote'], $container);

        $stateHandlerDefinition = new Definition('Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler', [
            new Reference('gssp.session'),
            $provider
        ]);
        $container->setDefinition('gssp.provider.' . $provider . '.statehandler', $stateHandlerDefinition);

        $providerDefinition = new Definition('Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider', [
            $provider,
            new Reference('gssp.provider.' . $provider . '.hosted.idp'),
            new Reference('gssp.provider.' . $provider . '.hosted.sp'),
            new Reference('gssp.provider.' . $provider . '.remote.idp'),
            new Reference('gssp.provider.' . $provider . '.statehandler')
        ]);

        $providerDefinition->setPublic(false);
        $container->setDefinition('gssp.provider.' . $provider, $providerDefinition);

        $assertionSigningService = new Definition('Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService', [
            new Reference('gssp.provider.' . $provider . '.hosted.idp')
        ]);
        $assertionSigningService->setPublic('false');
        $container->setDefinition('gssp.provider.' . $provider . '.assertion_signing', $assertionSigningService);

        $proxyResponseFactory = new Definition(
            'Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\ProxyResponseFactory',
            [
                new Reference('logger'),
                new Reference('gssp.provider.' . $provider . '.hosted.idp'),
                new Reference('gssp.provider.' . $provider . '.statehandler'),
                new Reference('gssp.provider.' . $provider . '.assertion_signing')
            ]
        );
        $container->setDefinition('gssp.provider.' . $provider . '.response_proxy', $proxyResponseFactory);

        $container
            ->getDefinition('gssp.provider_repository')
            ->addMethodCall('addProvider', [new Reference('gssp.provider.' . $provider)]);

        $viewConfigDefinition = new Definition('Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ViewConfig', [
            new Reference('request_stack'),
            $configuration['view_config']['logo'],
            $configuration['view_config']['title'],
        ]);
        $viewConfigDefinition->addTag(self::VIEW_CONFIG_TAG_NAME);

        $container->setDefinition('gssp.view_config.' . $provider, $viewConfigDefinition);
    }

    /**
     * @param string           $provider
     * @param array            $configuration
     * @param array            $routes
     * @param ContainerBuilder $container
     */
    private function createHostedDefinitions(
        $provider,
        array $configuration,
        array $routes,
        ContainerBuilder $container
    ) {
        $hostedDefinition = $this->buildHostedEntityDefinition($provider, $configuration, $routes);
        $container->setDefinition('gssp.provider.' . $provider . '.hosted_entities', $hostedDefinition);

        $hostedSpDefinition  = (new Definition())
            ->setClass('Surfnet\SamlBundle\Entity\ServiceProvider')
            ->setFactory([new Reference('gssp.provider.' . $provider . '.hosted_entities'), 'getServiceProvider'])
            ->setPublic(false);
        $container->setDefinition('gssp.provider.' . $provider . '.hosted.sp', $hostedSpDefinition);

        $hostedIdPDefinition = (new Definition())
            ->setClass('Surfnet\SamlBundle\Entity\IdentityProvider')
            ->setFactory([new Reference('gssp.provider.' . $provider . '.hosted_entities'), 'getIdentityProvider'])
            ->setPublic(false);
        $container->setDefinition('gssp.provider.' . $provider . '.hosted.idp', $hostedIdPDefinition);
    }

    /**
     * @param string $provider
     * @param array  $configuration
     * @param array  $routes
     * @return Definition
     */
    private function buildHostedEntityDefinition($provider, array $configuration, array $routes)
    {
        $entityId = ['entity_id_route' => $this->createRouteConfig($provider, $routes['metadata'])];
        $spAdditional = [
            'enabled' => true,
            'assertion_consumer_route' => $this->createRouteConfig($provider, $routes['consume_assertion'])
        ];
        $idpAdditional = [
            'enabled' => true,
            'sso_route' => $this->createRouteConfig($provider, $routes['sso'])
        ];

        $serviceProvider  = array_merge($configuration['service_provider'], $spAdditional, $entityId);
        $identityProvider = array_merge($configuration['identity_provider'], $idpAdditional, $entityId);

        $hostedDefinition = new Definition('Surfnet\SamlBundle\Entity\HostedEntities', [
            new Reference('router'),
            new Reference('request_stack'),
            $serviceProvider,
            $identityProvider
        ]);

        $hostedDefinition->setPublic(false);

        return $hostedDefinition;
    }

    /**
     * @param string           $provider
     * @param array            $configuration
     * @param ContainerBuilder $container
     */
    private function createRemoteDefinition($provider, array $configuration, ContainerBuilder $container)
    {
        $definition    = new Definition('Surfnet\SamlBundle\Entity\IdentityProvider', [
            [
                'entityId'        => $configuration['entity_id'],
                'ssoUrl'          => $configuration['sso_url'],
                'certificateData' => $configuration['certificate'],
            ]
        ]);

        $definition->setPublic(false);
        $container->setDefinition('gssp.provider.' . $provider . '.remote.idp', $definition);
    }

    /**
     * @param string           $provider
     * @param array            $configuration
     * @param array            $routes
     * @param ContainerBuilder $container
     * @return Definition
     */
    private function createMetadataDefinition(
        $provider,
        array $configuration,
        array $routes,
        ContainerBuilder $container
    ) {
        $metadataConfiguration = new Definition('Surfnet\SamlBundle\Metadata\MetadataConfiguration');

        $propertyMap = [
            'entityIdRoute'          => $this->createRouteConfig($provider, $routes['metadata']),
            'isSp'                   => true,
            'assertionConsumerRoute' => $this->createRouteConfig($provider, $routes['consume_assertion']),
            'isIdP'                  => true,
            'ssoRoute'               => $this->createRouteConfig($provider, $routes['sso']),
            'publicKey'              => $configuration['metadata']['public_key'],
            'privateKey'             => $configuration['metadata']['private_key'],
        ];

        $metadataConfiguration->setProperties($propertyMap);
        $metadataConfiguration->setPublic(false);
        $container->setDefinition('gssp.provider.' . $provider . 'metadata.configuration', $metadataConfiguration);

        $metadataFactory = new Definition('Surfnet\SamlBundle\Metadata\MetadataFactory', [
            new Reference('templating'),
            new Reference('router'),
            new Reference('surfnet_saml.signing_service'),
            new Reference('gssp.provider.' . $provider . 'metadata.configuration')
        ]);
        $container->setDefinition('gssp.provider.' . $provider . '.metadata.factory', $metadataFactory);
    }

    private function createRouteConfig($provider, $routeName)
    {
        return [
            'route'      => $routeName,
            'parameters' => ['provider' => $provider]
        ];
    }
}
