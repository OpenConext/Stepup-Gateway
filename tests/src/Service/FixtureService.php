<?php

namespace Surfnet\StepupGateway\Behat\Service;

use Exception;
use Surfnet\StepupGateway\Behat\Repository\SamlEntityRepository;
use Surfnet\StepupGateway\Behat\Repository\SecondFactorRepository;
use Surfnet\StepupGateway\Behat\Repository\WhitelistRepository;

class FixtureService
{
    private $secondFactorRepository;

    private $samlEntityRepository;

    private $whitelistRepository;

    public function __construct(
        SecondFactorRepository $secondFactorRepository,
        SamlEntityRepository $samlRepository,
        WhitelistRepository $whitelistRepository
    ) {
        $this->secondFactorRepository = $secondFactorRepository;
        $this->samlEntityRepository = $samlRepository;
        $this->whitelistRepository = $whitelistRepository;
    }

    /**
     * @param string $nameId
     * @param string $institution
     * @return array
     * @throws Exception
     */
    public function registerYubikeyToken($nameId, $institution)
    {
        return $this->secondFactorRepository->create($nameId, 'yubikey', $institution);
    }

    /**
     * @param string $nameId
     * @param string $institution
     * @return array
     * @throws Exception
     */
    public function registerSmsToken($nameId, $institution)
    {
        return $this->secondFactorRepository->create($nameId, 'sms', $institution, '+31 (0) 606060606');
    }

    /**
     * @param string $entityId
     * @param string $certificate
     * @param bool $sfoEnabled
     * @return array
     * @throws Exception
     */
    public function registerSP($entityId, $certificate, $sfoEnabled)
    {
        return $this->samlEntityRepository->createSpIfNotExists($entityId, $certificate, $sfoEnabled);
    }

    /**
     * @param string $institution
     * @return array
     * @throws Exception
     */
    public function whitelist($institution)
    {
        return $this->whitelistRepository->whitelist($institution);
    }
}
