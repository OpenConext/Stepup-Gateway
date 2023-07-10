<?php

namespace Surfnet\StepupGateway\Behat\Service;

use Exception;
use Surfnet\StepupGateway\Behat\Repository\InstitutionConfigurationRepository;
use Surfnet\StepupGateway\Behat\Repository\SamlEntityRepository;
use Surfnet\StepupGateway\Behat\Repository\SecondFactorRepository;
use Surfnet\StepupGateway\Behat\Repository\WhitelistRepository;

class FixtureService
{
    private $secondFactorRepository;

    private $samlEntityRepository;

    private $whitelistRepository;

    private $institutionConfigurationRepository;

    public function __construct(
        SecondFactorRepository $secondFactorRepository,
        SamlEntityRepository $samlRepository,
        WhitelistRepository $whitelistRepository,
        InstitutionConfigurationRepository $institutionConfigurationRepository
    ) {
        $this->secondFactorRepository = $secondFactorRepository;
        $this->samlEntityRepository = $samlRepository;
        $this->whitelistRepository = $whitelistRepository;
        $this->institutionConfigurationRepository = $institutionConfigurationRepository;
    }

    public function registerYubikeyToken(string $nameId, string $institution, bool $selfAsserted = false): array
    {
        if (!$this->secondFactorRepository->has($nameId, 'yubikey')) {
            return $this->secondFactorRepository->create($nameId, 'yubikey', $institution, $selfAsserted);
        }
        return $this->secondFactorRepository->findBy($nameId, 'yubikey');

    }

    /**
     * @param string $nameId
     * @param string $institution
     * @return array
     * @throws Exception
     */
    public function registerSmsToken(string $nameId, string $institution, bool $selfAsserted = false): array
    {
        if (!$this->secondFactorRepository->has($nameId, 'sms')) {
            return $this->secondFactorRepository->create($nameId, 'sms', $institution, $selfAsserted, '+31 (0) 606060606');
        }
        return $this->secondFactorRepository->findBy($nameId, 'sms');
    }

    /**
     * @param string $entityId
     * @param string $certificate
     * @param bool $sfoEnabled
     * @return array
     * @throws Exception
     */
    public function registerSP($entityId, $certificate, $sfoEnabled = false)
    {
        return $this->samlEntityRepository->createSpIfNotExists($entityId, $certificate, $sfoEnabled);
    }

    /**
     * @param string $entityId
     * @param string $certificate
     * @return array
     * @throws Exception
     */
    public function registerIdp($entityId, $certificate)
    {
        return $this->samlEntityRepository->createIdpIfNotExists($entityId, $certificate);
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

    public function registerTiqrToken(string $nameId, string $institution, bool $selfAsserted = false): array
    {
        if (!$this->secondFactorRepository->has($nameId, 'tiqr')) {
            return $this->secondFactorRepository->create($nameId, 'tiqr', $institution, $selfAsserted, 'foobar');
        }
        return $this->secondFactorRepository->findBy($nameId, 'tiqr');
    }

    public function configureBoolean(string $institution, string $option, bool $value): void
    {
        $this->institutionConfigurationRepository->configure($institution, $option, $value);
    }
}
