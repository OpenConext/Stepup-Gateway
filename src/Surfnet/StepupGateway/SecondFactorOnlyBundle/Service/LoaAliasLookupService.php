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

use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;

final class LoaAliasLookupService
{
    /**
     * @var array<string,string>
     */
    private $loaAliases;

    /**
     * @param array<string,string> $loaAliases
     */
    public function __construct(array $loaAliases)
    {
        foreach ($loaAliases as $loaId => $alias) {
            if (!is_string($loaId)) {
                throw InvalidArgumentException::invalidType(
                    'string',
                    'loaId',
                    $alias
                );
            }
            if (!is_string($alias)) {
                throw InvalidArgumentException::invalidType(
                    'string',
                    'alias',
                    $alias
                );
            }
        }

        $this->loaAliases = $loaAliases;
    }

    /**
     * @param string $alias
     * @return string|bool
     */
    public function findLoaIdByAlias($alias)
    {
        if (!is_string($alias)) {
            throw InvalidArgumentException::invalidType(
                'string',
                'alias',
                $alias
            );
        }
        return array_search($alias, $this->loaAliases);
    }

    /**
     * @param Loa $loa
     * @return string|bool
     */
    public function findAliasByLoa(Loa $loa)
    {
        foreach ($this->loaAliases as $loaId => $alias) {
            if ($loa->isIdentifiedBy($loaId)) {
                return $alias;
            }
        }
        return false;
    }
}
