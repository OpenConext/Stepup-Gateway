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

namespace Surfnet\StepupGateway\GatewayBundle\Test\Sso2fa;

use Exception;
use Mockery;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\CookieService;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto\CryptoHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Http\CookieHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\Configuration;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration test
 */
class CookieServiceTest extends TestCase
{
    /**
     * @var CookieService
     */
    private $service;

    /**
     * @var Configuration
     */
    private $configuration;

    protected function buildService(Configuration $configuration): void
    {
        $this->configuration = $configuration;
        $encryptionHelper = new CryptoHelper();
        $cookieHelper = new CookieHelper($this->configuration, $encryptionHelper);
        $this->service = new CookieService($cookieHelper);
    }

    public function test_storing_a_session_cookie()
    {
        $this->buildService(new Configuration('test-cookie', 'session', 0));
        $response = new Response('<html><body><h1>hi</h1></body></html>', 200);
        $cookieValue = $this->cookieValue();
        $this->service->store($response, $cookieValue);

        $cookieJar = $response->headers->getCookies();
        self::assertCount(1, $cookieJar);
        $cookie = reset($cookieJar);
        // The name and lifetime of the cookie should match the one we configured it to be
        self::assertEquals($this->configuration->getName(), $cookie->getName());
        self::assertEquals($this->configuration->getLifetime(), $cookie->getExpiresTime());
    }

    public function test_storing_a_persistent_cookie()
    {
        $this->buildService(new Configuration('test-cookie', 'persistent', 3600));
        $response = new Response('<html><body><h1>hi</h1></body></html>', 200);
        $cookieValue = $this->cookieValue();
        $this->service->store($response, $cookieValue);

        $cookieJar = $response->headers->getCookies();
        self::assertCount(1, $cookieJar);
        $cookie = reset($cookieJar);
        // The name and lifetime of the cookie should match the one we configured it to be
        self::assertEquals($this->configuration->getName(), $cookie->getName());
        self::assertEquals($this->configuration->getLifetime(), $cookie->getExpiresTime());
    }

    public function test_storing_fails_when_error_arises()
    {
        $this->buildService(new Configuration('test-cookie', 'persistent', 1));
        $response = new Response('<html><body><h1>hi</h1></body></html>', 200);
        $cookieValue = $this->cookieValue();
        $this->expectException(Exception::class);
        $this->service->store($response, $cookieValue);
    }

    private function cookieValue(): CookieValue
    {
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->secondFactorId = 'abcdef-1234';
        $secondFactor->identityId = 'abcdef-1234';
        $loa = new Loa(3.0, 'LoA3');
        return CookieValue::from($secondFactor, $loa);
    }
}
