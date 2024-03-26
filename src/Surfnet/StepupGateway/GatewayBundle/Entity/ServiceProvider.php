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

namespace Surfnet\StepupGateway\GatewayBundle\Entity;

use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\Entity\ServiceProvider as BaseServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\Exception\AcsLocationNotAllowedException;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * @return bool
     */
    public function mayUseGateway(): bool
    {
        return !$this->mayUseSecondFactorOnly();
    }

    /**
     * @return bool
     */
    public function mayUseSecondFactorOnly(): bool
    {
        return (bool) $this->get('secondFactorOnly', false);
    }

    /**
     * @param string $nameId
     * @return bool
     */
    public function isAllowedToUseSecondFactorOnlyFor($nameId): bool
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
            if ((bool) preg_match('#^' . strtr(preg_quote((string) $nameIdPattern, '#'), ['\*' => '.*']) . '$#', $nameId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine the ACS location to send the response to.
     *
     * The get getAssertionConsumerUrl() method returns a trusted ACS location
     * for this service provider. This value is set when the service provider
     * is "internal", for example when it is configured in yaml
     * configuration.
     *
     * Methods like Surfnet\SamlBundle\Http\PostBinding::processResponse use
     * this trusted value. When the ServiceProvider is external, this value is
     * empty and the ACS location found in the AuthnRequest should be used, if
     * it matches one of the configured allowed ACS locations for the service
     * provider. This methods checks if a given URL matches the allowed URLs.
     *
     * @param $acsLocationInAuthnRequest
     * @param LoggerInterface $logger Optional
     * @return string
     */
    public function determineAcsLocation($acsLocationInAuthnRequest, LoggerInterface $logger = null)
    {
        // List of allowed ACS locations configured in middleware.
        $allowedAcsLocations = $this->get('allowedAcsLocations');

        if (in_array($acsLocationInAuthnRequest, $allowedAcsLocations)) {
            return $acsLocationInAuthnRequest;
        }

        if ($logger !== null) {
            $logger->warning(
                sprintf(
                    'AuthnRequest requests ACS location "%s" but it is not configured in the list of allowed ACS ' .
                    'locations, allowed locations include: [%s]',
                    $acsLocationInAuthnRequest,
                    implode(', ', $allowedAcsLocations)
                )
            );
        }

        return reset($allowedAcsLocations);
    }

    /**
     * Determine the ACS location for ADFS to send the response to.
     *
     * This method is similar to determineAcsLocation(), but does not check
     * for the requested ACS location to be identical to one of the configured
     * ACS locations, but only if matches the first part of an allowed URL.
     *
     * For example, ADFS might send an ACS location like:
     *
     *     https://example.com/consume-assertion?key=value
     *
     * Above URL is allowed if one of the configured URLs is:
     *
     *     https://example.com/consume-assertion
     *
     * Or:
     *
     *     https://example.com/consume
     *
     *
     * @param $acsLocationInAuthnRequest
     * @return string
     */
    public function determineAcsLocationForAdfs($acsLocationInAuthnRequest)
    {
        // List of allowed ACS locations configured in middleware.
        $allowedAcsLocations = $this->get('allowedAcsLocations');

        foreach ($allowedAcsLocations as $allowedAcsLocation) {
            if (str_starts_with((string) $acsLocationInAuthnRequest, (string) $allowedAcsLocation)) {
                return $acsLocationInAuthnRequest;
            }
        }

        // The exception listener will log relevant information to the log.

        throw new AcsLocationNotAllowedException(
            $acsLocationInAuthnRequest
        );
    }

    public function allowSsoOn2fa(): bool
    {
        return $this->get('allowSsoOn2fa');
    }

    public function allowedToSetSsoCookieOn2fa(): bool
    {
        return $this->get('setSsoCookieOn2fa');
    }
}
