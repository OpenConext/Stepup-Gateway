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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\ProxyResponseFactory;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ViewConfig;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\HostedEntities;
use Surfnet\SamlBundle\Metadata\MetadataConfiguration;
use Surfnet\SamlBundle\Metadata\MetadataFactory;

/**
 * @SuppressWarnings(PHPMD.LongClassName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SurfnetStepupGatewaySamlStepupProviderExtension extends Extension
{
    public const VIEW_CONFIG_TAG_NAME = 'gssp.view_config';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(dirname(__DIR__, 5) . '/config'),
        );
        $loader->load('services.yaml');

        $connectedServiceProviders = $container->getDefinition('gssp.allowed_sps');
        $connectedServiceProviders->replaceArgument(0, $config['allowed_sps']);

        foreach ($config['providers'] as $provider => $providerConfiguration) {
            // may seem a bit strange, but this prevents casing issue when getting/setting/creating provider
            // service definitions etc.
            if ($provider !== strtolower((string) $provider)) {
                throw new InvalidConfigurationException('The provider name must be completely lowercase');
            }

            $this->loadProviderConfiguration($provider, $providerConfiguration, $config['routes'], $container);
        }
    }

    private function loadProviderConfiguration(
        string $provider,
        array $configuration,
        array $routes,
        ContainerBuilder $container,
    ): void {
        if ($container->has('gssp.provider.' . $provider)) {
            throw new InvalidConfigurationException(sprintf('Cannot create the same provider "%s" twice', $provider));
        }

        $this->createHostedDefinitions($provider, $configuration['hosted'], $routes, $container);
        $this->createMetadataDefinition($provider, $configuration['hosted'], $routes, $container);
        $this->createRemoteDefinition($provider, $configuration['remote'], $container);

        $stateHandlerDefinition = new Definition(StateHandler::class, [
            new Reference('request_stack'),
            $provider
        ]);
        $container->setDefinition('gssp.provider.' . $provider . '.statehandler', $stateHandlerDefinition);

        $providerDefinition = new Definition(Provider::class, [
            $provider,
            new Reference('gssp.provider.' . $provider . '.hosted.idp'),
            new Reference('gssp.provider.' . $provider . '.hosted.sp'),
            new Reference('gssp.provider.' . $provider . '.remote.idp'),
            new Reference('gssp.provider.' . $provider . '.statehandler')
        ]);

        $providerDefinition->setPublic(false);
        $container->setDefinition('gssp.provider.' . $provider, $providerDefinition);

        $assertionSigningService = new Definition(AssertionSigningService::class, [
            new Reference('gssp.provider.' . $provider . '.hosted.idp')
        ]);
        $assertionSigningService->setPublic('false');
        $container->setDefinition('gssp.provider.' . $provider . '.assertion_signing', $assertionSigningService);

        $proxyResponseFactory = new Definition(
            ProxyResponseFactory::class,
            [
                new Reference('logger'),
                new Reference('gssp.provider.' . $provider . '.hosted.idp'),
                new Reference('gssp.provider.' . $provider . '.statehandler'),
                new Reference('gssp.provider.' . $provider . '.assertion_signing')
            ],
        );
        $proxyResponseFactory->setPublic(true);
        $container->setDefinition('gssp.provider.' . $provider . '.response_proxy', $proxyResponseFactory);

        $container
            ->getDefinition('gssp.provider_repository')
            ->addMethodCall('addProvider', [new Reference('gssp.provider.' . $provider)]);

        $viewConfigDefinition = new Definition(ViewConfig::class, [
            new Reference('request_stack'),
            $configuration['view_config']['logo'],
            $configuration['view_config']['title'],
        ]);
        $viewConfigDefinition->addTag(self::VIEW_CONFIG_TAG_NAME);

        $container->setDefinition('gssp.view_config.' . $provider, $viewConfigDefinition);
    }

    private function createHostedDefinitions(
        string $provider,
        array $configuration,
        array $routes,
        ContainerBuilder $container,
    ): void {
        $hostedDefinition = $this->buildHostedEntityDefinition($provider, $configuration, $routes);
        $container->setDefinition('gssp.provider.' . $provider . '.hosted_entities', $hostedDefinition);

        $hostedSpDefinition  = (new Definition())
            ->setClass(ServiceProvider::class)
            ->setFactory([new Reference('gssp.provider.' . $provider . '.hosted_entities'), 'getServiceProvider'])
            ->setPublic(false);
        $container->setDefinition('gssp.provider.' . $provider . '.hosted.sp', $hostedSpDefinition);

        $hostedIdPDefinition = (new Definition())
            ->setClass(IdentityProvider::class)
            ->setFactory([new Reference('gssp.provider.' . $provider . '.hosted_entities'), 'getIdentityProvider'])
            ->setPublic(false);
        $container->setDefinition('gssp.provider.' . $provider . '.hosted.idp', $hostedIdPDefinition);
    }

    /**
     * @return Definition
     */
    private function buildHostedEntityDefinition(
        string $provider,
        array $configuration,
        array $routes,
    ): Definition {
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

        $hostedDefinition = new Definition(HostedEntities::class, [
            new Reference('router'),
            new Reference('request_stack'),
            $serviceProvider,
            $identityProvider
        ]);

        $hostedDefinition->setPublic(false);

        return $hostedDefinition;
    }

    private function createRemoteDefinition(string $provider, array $configuration, ContainerBuilder $container): void
    {
        $definition    = new Definition(IdentityProvider::class, [
            [
                'entityId'        => $configuration['entity_id'],
                'ssoUrl'          => $configuration['sso_url'],
                'certificateData' => $configuration['certificate'],
            ]
        ]);

        $definition->setPublic(false);
        $container->setDefinition('gssp.provider.' . $provider . '.remote.idp', $definition);
    }

    private function createMetadataDefinition(
        string $provider,
        array $configuration,
        array $routes,
        ContainerBuilder $container,
    ): void {
        $metadataConfiguration = new Definition(MetadataConfiguration::class);

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

        $metadataFactory = new Definition(MetadataFactory::class, [
            new Reference('twig'),
            new Reference('router'),
            new Reference('surfnet_saml.signing_service'),
            new Reference('gssp.provider.' . $provider . 'metadata.configuration')
        ]);
        $metadataFactory->setPublic(true);
        $container->setDefinition('gssp.provider.' . $provider . '.metadata.factory', $metadataFactory);
    }

    private function createRouteConfig(string $provider, $routeName): array
    {
        return [
            'route'      => $routeName,
            'parameters' => ['provider' => $provider]
        ];
    }
}
