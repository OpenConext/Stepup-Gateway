<?php
namespace Surfnet\StepupGateway\GatewayBundle\Entity\SamlEntityRepository;

use Surfnet\StepupGateway\GatewayBundle\Entity\SamlEntity;

interface SamlEntityRepositoryInterface
{
    /**
     * @param $entityId
     * @return null|SamlEntity
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findIdentityProvider($entityId);

    /**
     * @param $entityId
     * @return null|SamlEntity
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findServiceProvider($entityId);
}
