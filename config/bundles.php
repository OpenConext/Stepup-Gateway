<?php

return [
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle::class => ['dev' => true, 'smoketest' => true, 'test' => true],
    JMS\TranslationBundle\JMSTranslationBundle::class => ['all' => true],
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Nelmio\SecurityBundle\NelmioSecurityBundle::class => ['all' => true],
    Surfnet\StepupGateway\ApiBundle\SurfnetStepupGatewayApiBundle::class => ['all' => true],
    Surfnet\YubikeyApiClientBundle\SurfnetYubikeyApiClientBundle::class => ['all' => true],
    Surfnet\StepupBundle\SurfnetStepupBundle::class => ['all' => true],
    Surfnet\StepupGateway\GatewayBundle\SurfnetStepupGatewayGatewayBundle::class => ['all' => true],
    Surfnet\StepupGateway\SamlStepupProviderBundle\SurfnetStepupGatewaySamlStepupProviderBundle::class => ['all' => true],
    Surfnet\StepupGateway\SecondFactorOnlyBundle\SurfnetStepupGatewaySecondFactorOnlyBundle::class => ['all' => true],
    OpenConext\MonitorBundle\OpenConextMonitorBundle::class => ['all' => true],
    Surfnet\SamlBundle\SurfnetSamlBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'smoketest' => true, 'test' => true],
    Symfony\WebpackEncoreBundle\WebpackEncoreBundle::class => ['all' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
];
