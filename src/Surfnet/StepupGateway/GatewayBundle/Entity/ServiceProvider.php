<?php

namespace Surfnet\StepupGateway\GatewayBundle\Entity;

use Surfnet\SamlBundle\Entity\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function mayUseGateway()
    {
        return !$this->mayUseSecondFactorOnly();
    }

    public function mayUseSecondFactorOnly()
    {
        return (bool) $this->get('secondFactorOnly', false);
    }
}
