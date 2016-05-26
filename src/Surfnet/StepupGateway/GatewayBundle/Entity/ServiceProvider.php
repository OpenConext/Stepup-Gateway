<?php

namespace Surfnet\StepupGateway\GatewayBundle\Entity;

use Surfnet\SamlBundle\Entity\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * @return bool
     */
    public function mayUseGateway()
    {
        return !$this->mayUseSecondFactorOnly();
    }

    /**
     * @return bool
     */
    public function mayUseSecondFactorOnly()
    {
        return (bool) $this->get('secondFactorOnly', false);
    }

    /**
     * @param string $nameId
     * @return bool
     */
    public function isAllowedToUseSecondFactorOnlyFor($nameId)
    {
        if (empty($nameId)) {
            return false;
        }

        if (!$this->mayUseSecondFactorOnly()) {
            return false;
        }

        $nameIdPatterns = $this->get('secondFactorOnlyNameIdPatterns');
        foreach ($nameIdPatterns as $nameIdPattern) {
            if (fnmatch($nameIdPattern, $nameId)) {
                return true;
            }
        }
        return false;
    }
}
