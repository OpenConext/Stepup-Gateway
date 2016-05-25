<?php

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Entity\SamlEntityRepository;

use Surfnet\StepupGateway\GatewayBundle\Entity\SamlEntityRepository\SamlEntityRepositoryInterface;

final class SecondFactorOnlySamlEntityRepositoryDecorator
  implements SamlEntityRepositoryInterface
{
    /**
     * @var SamlEntityRepositoryInterface
     */
    private $repository;

    /**
     * SecondFactorOnlySamlEntityRepositoryDecorator constructor.
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

        if (!$serviceProvider->toServiceProvider()->mayUseSecondFactorOnly()) {
            return null;
        }

        return $serviceProvider;
    }
}
