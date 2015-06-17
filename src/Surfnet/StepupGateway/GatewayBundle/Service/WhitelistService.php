<?php

/**
 * Copyright 2014 SURFnet bv
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

use Surfnet\StepupGateway\GatewayBundle\Entity\WhitelistEntryRepository;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;

class WhitelistService
{
    /**
     * @var WhitelistEntryRepository
     */
    private $whitelistEntryRepository;

    public function __construct(WhitelistEntryRepository $whitelistEntryRepository)
    {
        $this->whitelistEntryRepository = $whitelistEntryRepository;
    }

    /**
     * @param string $institution
     * @return bool
     */
    public function contains($institution)
    {
        if (!is_string($institution)) {
            throw InvalidArgumentException::invalidType('string', 'institution', $institution);
        }

        return $this->whitelistEntryRepository->hasEntryFor($institution);
    }
}
