<?php

namespace Surfnet\StepupGateway\GatewayBundle\Service;

use Surfnet\StepupBundle\Service\LocaleProviderService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;

final class SecondFactorLocaleProvider implements LocaleProviderService
{
    /**
     * @var ProxyStateHandler
     */
    private $stateHandler;

    public function __construct(ProxyStateHandler $stateHandler)
    {
        $this->stateHandler = $stateHandler;
    }

    /**
     * @return string
     */
    public function determinePreferredLocale()
    {
        return (string) $this->stateHandler->getPreferredLocale();
    }
}
