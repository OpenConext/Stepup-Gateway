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

namespace Surfnet\StepupGateway\GatewayBundle\Sso2fa\Http;

use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto\CryptoHelperInterface;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\CookieNotFoundException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\Configuration;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieHelper implements CookieHelperInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var CryptoHelperInterface
     */
    private $encryptionHelper;

    public function __construct(Configuration $configuration, CryptoHelperInterface $encryptionHelper)
    {
        $this->configuration = $configuration;
        $this->encryptionHelper = $encryptionHelper;
    }

    public function write(Response $response, CookieValue $value): void
    {
        // The CookieValue is encrypted
        $encryptedCookieValue = $this->encryptionHelper->encrypt($value);
        // Create a Symfony HttpFoundation cookie object
        $cookie = $this->createCookieWithValue($encryptedCookieValue);
        // Which is added to the response headers
        $response->headers->setCookie($cookie);
    }

    /**
     * Retrieve the current cookie from the Request if it exists.
     */
    public function read(Request $request): CookieValue
    {
        if (!$request->cookies->has($this->configuration->getName())) {
            throw new CookieNotFoundException();
        }
        $cookie = $request->cookies->get($this->configuration->getName());
        return CookieValue::deserialize($cookie);
    }

    private function createCookieWithValue($value): Cookie
    {
        return new Cookie(
            $this->configuration->getName(),
            $value,
            $this->configuration->isPersistent() ? $this->configuration->getLifetime(): 0,
            '/',
            null,
            true,
            true,
            false,
            Cookie::SAMESITE_STRICT
        );
    }
}
