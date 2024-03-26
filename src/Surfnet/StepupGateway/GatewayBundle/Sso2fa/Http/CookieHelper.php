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

use Psr\Log\LoggerInterface;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto\CryptoHelperInterface;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\CookieNotFoundException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\Configuration;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValueInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieHelper implements CookieHelperInterface
{
    /**
     * By default, we set the cookie with the SameSite: NONE attribute.
     *
     * SameSite: NONE ensures the browser sends the cookie on cross domain requests. Which are typically performed
     * when doing SAML authentications. Using STRICT or LAX will cause the cookie not being sent in several scenarios.
     */
    private const SAME_SITE = Cookie::SAMESITE_NONE;

    public function __construct(private readonly Configuration $configuration, private readonly CryptoHelperInterface $encryptionHelper, private readonly LoggerInterface $logger)
    {
    }

    public function write(Response $response, CookieValueInterface $value): void
    {
        // The CookieValue is encrypted
        $encryptedCookieValue = $this->encryptionHelper->encrypt($value);
        $fingerprint = $this->hashFingerprint($encryptedCookieValue);
        $this->logger->notice(sprintf('Writing a SSO on 2FA cookie with fingerprint %s', $fingerprint));
        // Create a Symfony HttpFoundation cookie object
        $cookie = $this->createCookieWithValue($encryptedCookieValue);
        // Which is added to the response headers
        $response->headers->setCookie($cookie);
    }

    /**
     * Retrieve the current cookie from the Request if it exists.
     */
    public function read(Request $request): CookieValueInterface
    {
        if (!$request->cookies || !$request->cookies->has($this->configuration->getName())) {
            throw new CookieNotFoundException();
        }
        $cookie = $request->cookies->get($this->configuration->getName());
        $fingerprint = $this->hashFingerprint($cookie);
        $this->logger->notice(sprintf('Reading a SSO on 2FA cookie with fingerprint %s', $fingerprint));
        return $this->encryptionHelper->decrypt($cookie);
    }

    public function fingerprint(Request $request): string
    {
        if (!$request->cookies || !$request->cookies->has($this->configuration->getName())) {
            throw new CookieNotFoundException();
        }
        $cookie = $request->cookies->get($this->configuration->getName());
        return $this->hashFingerprint($cookie);
    }

    private function createCookieWithValue(string $value): Cookie
    {
        return new Cookie(
            $this->configuration->getName(),
            $value,
            $this->configuration->isPersistent() ? $this->getTimestamp($this->configuration->getLifetime()): 0,
            '/',
            null,
            true,
            true,
            false,
            self::SAME_SITE
        );
    }

    private function hashFingerprint(string $encryptedCookieValue): string
    {
        return hash('sha256', $encryptedCookieValue);
    }

    private function getTimestamp(int $expiresInSeconds): int
    {
        $currentTimestamp = time();
        return $currentTimestamp + $expiresInSeconds;
    }
}
