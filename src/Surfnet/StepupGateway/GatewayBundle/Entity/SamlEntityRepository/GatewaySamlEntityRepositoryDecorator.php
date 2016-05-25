<?php

namespace Surfnet\StepupGateway\GatewayBundle\Entity\SamlEntityRepository;

final class GatewaySamlEntityRepositoryDecorator implements SamlEntityRepositoryInterface
{
    /**
     * @var SamlEntityRepositoryInterface
     */
    private $repository;

    /**
     * GatewaySamlEntityRepositoryDecorator constructor.
     * @param SamlEntityRepositoryInterface $repository
     */
    public function __construct(SamlEntityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function findIdentityProvider($entityId)
    {
        return $this->repository->findIdentityProvider($entityId);
    }

    public function findServiceProvider($entityId)
    {
        $serviceProvider = $this->repository->findServiceProvider($entityId);

        if (!$serviceProvider->toServiceProvider()->mayUseGateway()) {
            return null;
        }

        return $serviceProvider;
    }
}
