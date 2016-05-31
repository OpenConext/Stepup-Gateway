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

namespace Surfnet\StepupGateway\GatewayBundle\Entity;

use Surfnet\SamlBundle\Entity\ServiceProvider as BaseServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;

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
        if (!is_string($nameId)) {
            throw InvalidArgumentException::invalidType('string', 'nameId', $nameId);
        }

        if (empty($nameId)) {
            return false;
        }

        if (!$this->mayUseSecondFactorOnly()) {
            return false;
        }

        $nameIdPatterns = $this->get('secondFactorOnlyNameIdPatterns');
        foreach ($nameIdPatterns as $nameIdPattern) {
            if ($this->match($nameIdPattern, $nameId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does a given wildcard pattern match a string?
     *
     * Adapted from: http://php.net/manual/en/function.fnmatch.php
     * @param string $pattern
     * @param string $string
     * @return bool
     */
    private function match($pattern, $string) {
        $modifiers = null;
        $transforms = array(
            '\*'    => '.*',
        );

        $pattern = '#^'
            . strtr(preg_quote($pattern, '#'), $transforms)
            . '$#'
            . $modifiers;

        return (boolean) preg_match($pattern, $string);
    }
}
