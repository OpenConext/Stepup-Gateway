<?php declare(strict_types=1);

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

use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValueInterface;

/**
 * Used to verify if the authentication time from the CookieValue
 * surpasses the current timestamp. Which is determined by adding
 * the cookie lifetime to the authentication time. And checking that
 * against the current timestamp.
 *
 * The current timestamp can be set on this helper class in order
 * to make testing more predictable. However, if this is not set
 * explicitly it will use 'now' as the current timestamp.
 */
interface ExpirationHelperInterface
{
    public function isExpired(CookieValueInterface $cookieValue): bool;
}
