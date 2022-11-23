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

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\InstitutionConfigurationService;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\CookieService;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto\CryptoHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto\HaliteCryptoHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Http\CookieHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\Configuration;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaResolutionService as SfoLoaResolutionService;
use Symfony\Component\HttpFoundation\Request;
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
    /**
     * @var ResponseContext&Mockery\MockInterface
     */
    private $responseContext;
    /**
     * @var InstitutionConfigurationService&Mockery\MockInterface
     */
    private $institutionService;
    /**
     * @var LoaResolutionService&Mockery\MockInterface
     */
    private $gwLoaResolution;
    /**
     * @var SfoLoaResolutionService&Mockery\MockInterface
     */
    private $sfoLoaResolution;
    /**
     * @var SecondFactorService&Mockery\MockInterface
     */
    private $secondFactorService;

    protected function buildService(Configuration $configuration): void
    {
        // Not all dependencies are included for real, the ones not focussed on crypto and cookie storage are mocked
        $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $this->institutionService = Mockery::mock(InstitutionConfigurationService::class);
        $this->gwLoaResolution = Mockery::mock(LoaResolutionService::class);
        $this->sfoLoaResolution = Mockery::mock(SfoLoaResolutionService::class);
        $this->secondFactorService = Mockery::mock(SecondFactorService::class);

        $this->configuration = $configuration;
        $encryptionHelper = new HaliteCryptoHelper($configuration);
        $cookieHelper = new CookieHelper($this->configuration, $encryptionHelper, $logger);
        $this->service = new CookieService(
            $cookieHelper,
            $this->institutionService,
            $this->gwLoaResolution,
            $this->sfoLoaResolution,
            $logger,
            $this->secondFactorService
        );

        $this->responseContext = Mockery::mock(ResponseContext::class);
    }

    public function test_storing_a_session_cookie()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $response = new Response('<html><body><h1>hi</h1></body></html>', 200);
        $request = Mockery::mock(Request::class);
        $sfMock = Mockery::mock(SecondFactor::class)->makePartial();
        $sfMock->secondFactorId = 'sf-id-1234';
        $sfMock->institution = 'institution-a';

        $this->responseContext
            ->shouldReceive('getSelectedSecondFactor')
            ->andReturn('sf-id-1234');
        $this->responseContext
            ->shouldReceive('unsetSelectedSecondFactor');
        $this->secondFactorService
            ->shouldReceive('findByUuid')
            ->with('sf-id-1234')
            ->andReturn($sfMock);
        $this->institutionService
            ->shouldReceive('ssoOn2faEnabled')
            ->with('institution-a')
            ->andReturn(true);
        $this->responseContext
            ->shouldReceive('getRequiredLoa')
            ->andReturn('example.org:loa-2.0');
        $this->gwLoaResolution
            ->shouldReceive('getLoa')
            ->with('example.org:loa-2.0')
            ->andReturn(new Loa(2.0, 'example.org:loa-2.0'));
        $this->responseContext
            ->shouldReceive('getIdentityNameId')
            ->andReturn('james-hoffman');

        $response = $this->service->handleSsoOn2faCookieStorage($this->responseContext, $request, $response);

        $cookieJar = $response->headers->getCookies();
        self::assertCount(1, $cookieJar);
        $cookie = reset($cookieJar);
        // The name and lifetime of the cookie should match the one we configured it to be
        self::assertEquals($this->configuration->getName(), $cookie->getName());
        self::assertEquals($this->configuration->getLifetime(), $cookie->getExpiresTime());
    }

    public function test_storing_a_persistent_cookie()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'persistent',
                3600,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $response = new Response('<html><body><h1>hi</h1></body></html>', 200);
        $request = Mockery::mock(Request::class);

        $sfMock = Mockery::mock(SecondFactor::class)->makePartial();
        $sfMock->secondFactorId = 'sf-id-1234';
        $sfMock->institution = 'institution-a';

        $this->responseContext
            ->shouldReceive('getSelectedSecondFactor')
            ->andReturn('sf-id-1234');
        $this->responseContext
            ->shouldReceive('unsetSelectedSecondFactor');
        $this->secondFactorService
            ->shouldReceive('findByUuid')
            ->with('sf-id-1234')
            ->andReturn($sfMock);
        $this->institutionService
            ->shouldReceive('ssoOn2faEnabled')
            ->with('institution-a')
            ->andReturn(true);
        $this->responseContext
            ->shouldReceive('getRequiredLoa')
            ->andReturn('example.org:loa-2.0');
        $this->gwLoaResolution
            ->shouldReceive('getLoa')
            ->with('example.org:loa-2.0')
            ->andReturn(new Loa(2.0, 'example.org:loa-2.0'));
        $this->responseContext
            ->shouldReceive('getIdentityNameId')
            ->andReturn('james-hoffman');

        $response = $this->service->handleSsoOn2faCookieStorage($this->responseContext, $request, $response);

        $cookieJar = $response->headers->getCookies();
        self::assertCount(1, $cookieJar);
        $cookie = reset($cookieJar);
        // The name and lifetime of the cookie should match the one we configured it to be
        self::assertEquals($this->configuration->getName(), $cookie->getName());
        self::assertEquals(time() + $this->configuration->getLifetime(), $cookie->getExpiresTime());
    }

    public function test_storing_a_session_cookie_second_factor_not_found()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $response = new Response('<html><body><h1>hi</h1></body></html>', 200);
        $request = Mockery::mock(Request::class);

        $this->responseContext
            ->shouldReceive('getSelectedSecondFactor')
            ->andReturn('non-existant');
        $this->responseContext
            ->shouldReceive('unsetSelectedSecondFactor');
        $this->secondFactorService
            ->shouldReceive('findByUuid')
            ->with('non-existant')
            ->andReturnNull();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Second Factor token not found with ID: non-existant');
        $this->service->handleSsoOn2faCookieStorage($this->responseContext, $request, $response);
    }

    public function test_storing_a_session_cookie_not_enabled_for_institution()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $response = new Response('<html><body><h1>hi</h1></body></html>', 200);
        $request = Mockery::mock(Request::class);
        $sfMock = Mockery::mock(SecondFactor::class)->makePartial();
        $sfMock->secondFactorId = 'sf-id-1234';
        $sfMock->institution = 'institution-a';

        $this->responseContext
            ->shouldReceive('getSelectedSecondFactor')
            ->andReturn('sf-id-1234');
        $this->responseContext
            ->shouldReceive('unsetSelectedSecondFactor');
        $this->secondFactorService
            ->shouldReceive('findByUuid')
            ->with('sf-id-1234')
            ->andReturn($sfMock);
        $this->institutionService
            ->shouldReceive('ssoOn2faEnabled')
            ->with('institution-a')
            ->andReturn(false);

        $response = $this->service->handleSsoOn2faCookieStorage($this->responseContext, $request, $response);

        $cookieJar = $response->headers->getCookies();
        self::assertCount(0, $cookieJar);
    }

    private function cookieValue(): CookieValue
    {
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->secondFactorId = 'abcdef-1234';
        $secondFactor->identityId = 'abcdef-1234';
        $loa = new Loa(3.0, 'LoA3');
        return CookieValue::from($secondFactor->identityId, $secondFactor->secondFactorId, $loa->getLevel());
    }
}
