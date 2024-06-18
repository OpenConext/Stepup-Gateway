<?php declare(strict_types=1);

/**
 * Copyright 2022 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupGateway\GatewayBundle\Service;

use Psr\Log\LoggerInterface;
use Surfnet\StepupGateway\GatewayBundle\Entity\InstitutionConfigurationRepository;
use Surfnet\StepupGateway\GatewayBundle\Exception\InstitutionConfigurationNotFoundException;

class InstitutionConfigurationService
{
    private $repository;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        InstitutionConfigurationRepository $institutionConfigurationRepository,
        LoggerInterface $logger
    ) {
        $this->repository = $institutionConfigurationRepository;
        $this->logger = $logger;
    }


    public function ssoOn2faEnabled(string $institution): bool
    {
        try {
            return $this->repository->getInstitutionConfiguration($institution)->ssoOn2faEnabled;
        } catch (InstitutionConfigurationNotFoundException $e) {
            $this->logger->notice(sprintf('Institution %s is not configured to use SSO on 2FA', $institution));
        }
        return false;
    }
}
