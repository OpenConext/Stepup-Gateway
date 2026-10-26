<?php

/**
 * Copyright 2026 SURFnet bv
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

namespace Surfnet\StepupGateway\Behat\Service;

use Surfnet\StepupGateway\GatewayBundle\Configuration\FeatureConfiguration;

// Static, because the Behat kernel reboots before every request and drops container overrides.
class FeatureToggle
{
    private static bool $serviceNameDisabled = false;

    public static function disableServiceName(): void
    {
        self::$serviceNameDisabled = true;
    }

    /**
     * Called from a BeforeScenario hook so a disable in one scenario never leaks into the next.
     */
    public static function reset(): void
    {
        self::$serviceNameDisabled = false;
    }

    public static function createFeatureConfiguration(): FeatureConfiguration
    {
        return new FeatureConfiguration(!self::$serviceNameDisabled);
    }
}
