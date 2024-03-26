<?php

declare(strict_types=1);

/**
 * Copyright 2023 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Sso2fa\DateTime;

use DateTime as CoreDateTime;
use Surfnet\StepupBundle\DateTime\DateTime;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\InvalidAuthenticationTimeException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValueInterface;
use TypeError;

class ExpirationHelper implements ExpirationHelperInterface
{
    public function __construct(
        /**
        * The SSO on 2FA cookie lifetime in seconds
        * See: config/openconext/parameters.yaml sso_cookie_lifetime
        */
        private readonly int $cookieLifetime,
        /**
         * The period in seconds that we still acknowledge the
         * cookie even tho the expiration was reached. This accounts
         * for server time/sync differences that may occur.
         */
        private readonly int $gracePeriod,
        private ?CoreDateTime $now = null
    )
    {
        if ($now === null) {
            $now = DateTime::now();
        }
        $this->now = $now;
    }

    public function isExpired(CookieValueInterface $cookieValue): bool
    {
        try {
            $authenticationTimestamp = $cookieValue->authenticationTime();
        } catch (TypeError $error) {
            throw new InvalidAuthenticationTimeException(
                'The authentication time contained a non-int value',
                0,
                $error
            );
        }

        if ($authenticationTimestamp < 0) {
            throw new InvalidAuthenticationTimeException(
                'The authentication time is from before the Unix timestamp epoch'
            );
        }

        if ($authenticationTimestamp > $this->now->getTimestamp()) {
            throw new InvalidAuthenticationTimeException(
                'The authentication time is from the future, which indicates the clock settings ' .
                'are incorrect, or the time in the cookie value was tampered with.'
            );
        }

        $expirationTimestamp = $authenticationTimestamp + $this->cookieLifetime + $this->gracePeriod;
        $currentTimestamp = $this->now->getTimestamp();

        // Is the current time greater than the expiration time?
        return $currentTimestamp > $expirationTimestamp;
    }
}
