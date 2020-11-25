<?php

return [
  Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
  Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle::class => ['all' => true],
  Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
  JMS\TranslationBundle\JMSTranslationBundle::class => ['all' => true],
  Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
  Nelmio\SecurityBundle\NelmioSecurityBundle::class => ['all' => true],
  Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],
  Surfnet\StepupGateway\ApiBundle\SurfnetStepupGatewayApiBundle::class => ['all' => true],
  Surfnet\MessageBirdApiClientBundle\SurfnetMessageBirdApiClientBundle::class => ['all' => true],
  Surfnet\YubikeyApiClientBundle\SurfnetYubikeyApiClientBundle::class => ['all' => true],
  Surfnet\StepupBundle\SurfnetStepupBundle::class => ['all' => true],
  Surfnet\StepupGateway\GatewayBundle\SurfnetStepupGatewayGatewayBundle::class => ['all' => true],
  Surfnet\StepupGateway\SamlStepupProviderBundle\SurfnetStepupGatewaySamlStepupProviderBundle::class => ['all' => true],
  Surfnet\StepupGateway\SecondFactorOnlyBundle\SurfnetStepupGatewaySecondFactorOnlyBundle::class => ['all' => true],
  Surfnet\StepupGateway\U2fVerificationBundle\SurfnetStepupGatewayU2fVerificationBundle::class => ['all' => true],
  Surfnet\StepupU2fBundle\SurfnetStepupU2fBundle::class => ['all' => true],
  OpenConext\MonitorBundle\OpenConextMonitorBundle::class => ['all' => true],
  Surfnet\SamlBundle\SurfnetSamlBundle::class => ['all' => true],
  Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
  Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
  Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle::class => ['all' => true],
  Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
  Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => [
    'dev' => true,
    'test' => true,
  ],
  Symfony\WebpackEncoreBundle\WebpackEncoreBundle::class => ['all' => true],
];
