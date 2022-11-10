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

namespace Surfnet\StepupGateway\GatewayBundle\Sso2fa;

use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Http\CookieHelperInterface;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;
use Symfony\Component\HttpFoundation\Response;

class CookieService implements CookieServiceInterface
{
    /**
     * @var CookieHelperInterface
     */
    private $cookieHelper;

    public function __construct(CookieHelperInterface $cookieHelper)
    {
        $this->cookieHelper = $cookieHelper;
    }

    public function store(Response $response, CookieValue $cookieValue)
    {
        $this->cookieHelper->write($response, $cookieValue);
    }
}
