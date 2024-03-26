<?php

/**
 * Copyright 2016 SURFnet bv
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Service;

use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Service\LoaResolutionService as BaseResolutionService;

class LoaResolutionService
{
    public function __construct(
        private LoggerInterface $logger,
        private readonly LoaAliasLookupService $loaAliasLookup,
        private readonly BaseResolutionService $loaResolution,
    ) {
    }

    /**
     * @return $this
     */
    public function with(LoggerInterface $logger): static
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Resolves a LOA id.
     *
     * Returns the LOA id or an empty string if there is an issue.
     *
     * @param string $loaId
     *   AuthnContextClassRef provided in AuthnRequest.
     *
     * @return string
     *   LOA Id
     */
    public function resolve($loaId): string
    {
        if (empty($loaId)) {
            $this->logger->notice('No LOA requested, sending response with status Requester Error');
            return '';
        }

        $derefLoaId = $this->loaAliasLookup->findLoaIdByAlias($loaId);

        if (!$derefLoaId) {
            $this->logger->notice(sprintf(
                'Requested required Loa "%s" does not have a second factor alias',
                $loaId,
            ));
            return '';
        }

        if (!$this->loaResolution->hasLoa($derefLoaId)) {
            $this->logger->notice(sprintf(
                'Requested required Loa "%s" does not exist',
                $derefLoaId,
            ));
            return '';
        }

        return $derefLoaId;
    }
}
