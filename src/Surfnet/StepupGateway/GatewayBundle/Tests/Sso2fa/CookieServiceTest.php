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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\InstitutionConfigurationService;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\CookieService;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto\CryptoHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto\HaliteCryptoHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\DateTime\ExpirationHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\DateTime\ExpirationHelperInterface;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Http\CookieHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\Configuration;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaResolutionService as SfoLoaResolutionService;
use Symfony\Component\HttpFoundation\Cookie;
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
    /**
     * @var SecondFactorTypeService&Mockery\MockInterface
     */
    private $secondFactorTypeService;
    /**
     * @var HaliteCryptoHelper
     */
    private $encryptionHelper;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExpirationHelperInterface|(ExpirationHelperInterface&Mockery\LegacyMockInterface)|(ExpirationHelperInterface&Mockery\MockInterface)
     */
    private $expirationHelper;

    protected function buildService(Configuration $configuration): void
    {
        // Not all dependencies are included for real, the ones not focussed on crypto and cookie storage are mocked
        $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $this->institutionService = Mockery::mock(InstitutionConfigurationService::class);
        $this->secondFactorService = Mockery::mock(SecondFactorService::class);
        $this->secondFactorTypeService = Mockery::mock(SecondFactorTypeService::class);
        $this->configuration = $configuration;
        $this->encryptionHelper = new HaliteCryptoHelper($configuration);
        $this->expirationHelper = Mockery::mock(ExpirationHelperInterface::class);
        $cookieHelper = new CookieHelper($this->configuration, $this->encryptionHelper, $logger);
        $this->service = new CookieService(
            $cookieHelper,
            $this->institutionService,
            $this->secondFactorService,
            $this->secondFactorTypeService,
            $this->expirationHelper,
            $logger
        );

        $this->responseContext = Mockery::mock(ResponseContext::class);
        $this->responseContext
            ->shouldReceive('isForceAuthn')
            ->andReturnFalse();

        $sp = Mockery::mock(ServiceProvider::class);
        $sp
            ->shouldReceive('getEntityId')
            ->andReturn('https://remote.sp.stepup.example.com');
        $sp
            ->shouldReceive('allowedToSetSsoCookieOn2fa')
            ->andReturnTrue();
        $sp
            ->shouldReceive('allowSsoOn2fa')
            ->andReturnTrue();
        $this->responseContext
            ->shouldReceive('getServiceProvider')
            ->andReturn($sp);
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
        $sfMock->secondFactorType = 'sms';
        $sfMock->identityVetted = true;

        $this->responseContext
            ->shouldReceive('getSelectedSecondFactor')
            ->andReturn('sf-id-1234');
        $this->responseContext
            ->shouldReceive('finalizeAuthentication');
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
        $this->responseContext
            ->shouldReceive('getIdentityNameId')
            ->andReturn('james-hoffman');
        $this->secondFactorTypeService
            ->shouldReceive('getLevel')
            ->andReturn(2.0);

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
        $sfMock->secondFactorType = 'yubikey';
        $sfMock->identityVetted = true;

        $this->responseContext
            ->shouldReceive('getSelectedSecondFactor')
            ->andReturn('sf-id-1234');
        $this->responseContext
            ->shouldReceive('finalizeAuthentication');
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
        $this->responseContext
            ->shouldReceive('getIdentityNameId')
            ->andReturn('james-hoffman');
        $this->secondFactorTypeService
            ->shouldReceive('getLevel')
            ->andReturn(3.0);

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
            ->shouldReceive('finalizeAuthentication');
        $this->secondFactorService
            ->shouldReceive('findByUuid')
            ->with('non-existant')
            ->andReturnNull();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Second Factor token not found with ID: non-existant');
        $this->service->handleSsoOn2faCookieStorage($this->responseContext, $request, $response);
    }

    public function test_storing_a_session_cookie_disallowed_sp()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $this->responseContext = Mockery::mock(ResponseContext::class);
        $sp = Mockery::mock(ServiceProvider::class);
        $sp
            ->shouldReceive('getEntityId')
            ->andReturn('https://ra.stepup.example.com/gssf/tiqr/metadata');
        $sp
            ->shouldReceive('allowedToSetSsoCookieOn2fa')
            ->andReturnFalse();
        $this->responseContext
            ->shouldReceive('getServiceProvider')
            ->andReturn($sp);

        $response = new Response('<html><body><h1>hi</h1></body></html>', 200);
        $request = Mockery::mock(Request::class);

        $this->service->handleSsoOn2faCookieStorage($this->responseContext, $request, $response);
        $cookieJar = $response->headers->getCookies();
        self::assertCount(0, $cookieJar);
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
            ->shouldReceive('finalizeAuthentication');
        $this->responseContext
            ->shouldReceive('isForceAuthn')
            ->andReturn(false);
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

    public function test_skipping_authentication_succeeds()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $yubikey = $this->buildSecondFactor(3.0, 'identifier');
        $collection = new ArrayCollection([
            $yubikey,
        ]);

        $httpRequest = new Request();
        $cookieValue = $this->cookieValue();
        $httpRequest->cookies->add(
            [$this->configuration->getName() => $this->createCookieWithValue($this->encryptionHelper->encrypt($cookieValue))->getValue()]
        );

        $this->responseContext
            ->shouldReceive('saveSelectedSecondFactor')
            ->with($yubikey);

        $this->expirationHelper
            ->shouldReceive('isExpired')
            ->andReturn(false);

        $token = Mockery::mock(SecondFactor::class);
        $this->secondFactorService
            ->shouldReceive('findByUuid')
            ->andReturn($token);

        self::assertTrue(
            $this->service->shouldSkip2faAuthentication(
                $this->responseContext,
                3.0,
                'abcdef-1234',
                $collection,
                $httpRequest
            )
        );
    }

    public function test_skipping_authentication_succeeds_selects_correct_token()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $yubikey = $this->buildSecondFactor(3.0, 'identifier-1');
        $sms = $this->buildSecondFactor(2.0, 'identifier-2');
        $bogus = $this->buildSecondFactor(2.0, 'identifier-3');
        $collection = new ArrayCollection([
            $sms,
            $bogus,
            $yubikey,
        ]);

        $httpRequest = new Request();
        $cookieValue = $this->cookieValue();
        $httpRequest->cookies->add(
            [$this->configuration->getName() => $this->createCookieWithValue($this->encryptionHelper->encrypt($cookieValue))->getValue()]
        );

        // The Yubikey is the only suitable token available that satisfies the LoA requirement
        $this->responseContext
            ->shouldReceive('saveSelectedSecondFactor')
            ->with($yubikey);

        $this->expirationHelper
            ->shouldReceive('isExpired')
            ->andReturn(false);

        $token = Mockery::mock(SecondFactor::class);
        $this->secondFactorService
            ->shouldReceive('findByUuid')
            ->andReturn($token);

        self::assertTrue(
            $this->service->shouldSkip2faAuthentication(
                $this->responseContext,
                3.0,
                'abcdef-1234',
                $collection,
                $httpRequest
            )
        );
    }

    public function test_skipping_authentication_fails_when_no_sso_cookie_present()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $httpRequest = new Request();

        self::assertFalse(
            $this->service->shouldSkip2faAuthentication(
                $this->responseContext,
                3.0,
                'abcdef-1234',
                Mockery::mock(ArrayCollection::class),
                $httpRequest
            )
        );
    }

    public function test_skip_sso_when_sp_disallows_sso_on_2fa()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );

        $this->responseContext = Mockery::mock(ResponseContext::class);
        $sp = Mockery::mock(ServiceProvider::class);
        $sp
            ->shouldReceive('getEntityId')
            ->andReturn('https://ra.stepup.example.com/vetting-procedure/gssf/tiqr/metadata');
        $sp
            ->shouldReceive('allowSsoOn2fa')
            ->andReturnFalse();
        $this->responseContext
            ->shouldReceive('getServiceProvider')
            ->andReturn($sp);
        $this->responseContext
            ->shouldReceive('isForceAuthn')->andReturn(false);

        $cookieValue = $this->cookieValue();
        $httpRequest = new Request();
        $httpRequest->cookies->add(
            [$this->configuration->getName() => $this->createCookieWithValue($this->encryptionHelper->encrypt($cookieValue))->getValue()]
        );

        self::assertFalse(
            $this->service->shouldSkip2faAuthentication(
                $this->responseContext,
                3.0,
                'abcdef-1234',
                Mockery::mock(ArrayCollection::class),
                $httpRequest
            )
        );
    }

    public function test_skipping_authentication_fails_when_no_sso_cookie_has_too_low_of_a_loa()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $httpRequest = new Request();
        $cookieValue = $this->cookieValue();
        $httpRequest->cookies->add(
            [$this->configuration->getName() => $this->createCookieWithValue($this->encryptionHelper->encrypt($cookieValue))->getValue()]
        );
        self::assertFalse(
            $this->service->shouldSkip2faAuthentication(
                $this->responseContext,
                4.0, // LoA required by SP is 4.0, the one in the cookie is 3.0
                'abcdef-1234',
                Mockery::mock(ArrayCollection::class),
                $httpRequest
            )
        );
    }

    public function test_skipping_authentication_fails_when_force_authn_requested()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $httpRequest = new Request();
        $cookieValue = $this->cookieValue();
        $httpRequest->cookies->add(
            [$this->configuration->getName() => $this->createCookieWithValue($this->encryptionHelper->encrypt($cookieValue))->getValue()]
        );
        $this->responseContext = Mockery::mock(ResponseContext::class);
        $this->responseContext
            ->shouldReceive('isForceAuthn')->andReturn(true);

        self::assertFalse(
            $this->service->shouldSkip2faAuthentication(
                $this->responseContext,
                3.0,
                'abcdef-1234',
                Mockery::mock(ArrayCollection::class),
                $httpRequest
            )
        );
    }

    public function test_skipping_authentication_fails_when_identity_id_doesnt_match()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $httpRequest = new Request();
        $cookieValue = $this->cookieValue();
        $httpRequest->cookies->add(
            [$this->configuration->getName() => $this->createCookieWithValue($this->encryptionHelper->encrypt($cookieValue))->getValue()]
        );
        self::assertFalse(
            $this->service->shouldSkip2faAuthentication(
                $this->responseContext,
                2.0,
                'Jane Doe', // Not issued to Jane Doe but to abcdef-1234
                Mockery::mock(ArrayCollection::class),
                $httpRequest
            )
        );
    }

    public function test_skipping_authentication_fails_when_no_suitable_available_token_is_present()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $yubikey = $this->buildSecondFactor(3.0, 'identifier-1');
        $sms = $this->buildSecondFactor(2.0, 'identifier-2');
        $bogus = $this->buildSecondFactor(2.0, 'identifier-3');
        $collection = new ArrayCollection([
            $sms,
            $bogus,
            $yubikey,
        ]);

        $httpRequest = new Request();
        $cookieValue = $this->cookieValue();
        $httpRequest->cookies->add(
            [$this->configuration->getName() => $this->createCookieWithValue($this->encryptionHelper->encrypt($cookieValue))->getValue()]
        );

        // The Yubikey is the only suitable token available that satisfies the LoA requirement
        $this->responseContext
            ->shouldReceive('saveSelectedSecondFactor')
            ->with($yubikey);

        self::assertFalse(
            $this->service->shouldSkip2faAuthentication(
                $this->responseContext,
                4.0, // LoA 4 is required, Identity only has LoA 2 and 3 tokens, no bueno
                'abcdef-1234',
                $collection,
                $httpRequest
            )
        );
    }

    public function test_skipping_authentication_fails_when_token_was_revoked()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $yubikey = $this->buildSecondFactor(3.0, 'identifier-1');
        $sms = $this->buildSecondFactor(2.0, 'identifier-2');
        $bogus = $this->buildSecondFactor(2.0, 'identifier-3');
        $collection = new ArrayCollection([
            $sms,
            $bogus,
            $yubikey,
        ]);

        $httpRequest = new Request();
        $cookieValue = $this->cookieValue();
        $httpRequest->cookies->add(
            [$this->configuration->getName() => $this->createCookieWithValue($this->encryptionHelper->encrypt($cookieValue))->getValue()]
        );

        // The Yubikey is the only suitable token available that satisfies the LoA requirement
        $this->responseContext
            ->shouldReceive('saveSelectedSecondFactor')
            ->with($yubikey);

        $this->expirationHelper
            ->shouldReceive('isExpired')
            ->andReturn(false);

        // When the token cannot be found e.g. token was revoked in the meantime
        $this->secondFactorService
            ->shouldReceive('findByUuid')
            ->andReturnNull();

        self::assertFalse(
            $this->service->shouldSkip2faAuthentication(
                $this->responseContext,
                3.0,
                'abcdef-1234',
                $collection,
                $httpRequest
            )
        );
    }

    private function cookieValue(): CookieValue
    {
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->secondFactorId = 'abcdef-1234';
        $secondFactor->identityId = 'abcdef-1234';
        $loa = new Loa(3.0, 'LoA3');
        return CookieValue::from($secondFactor->identityId, $secondFactor->secondFactorId, $loa->getLevel());
    }

    private function createCookieWithValue($value): Cookie
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
            Cookie::SAMESITE_STRICT
        );
    }

    private function buildSecondFactor(float $loaLevel, string $identifier): SecondFactor
    {
        $token = Mockery::mock(SecondFactor::class)->makePartial();
        $token->secondFactorId = $identifier;
        $token->displayLocale = 'nl';
        $token->shouldReceive('getLoaLevel')->andReturn($loaLevel);
        return $token;
    }
}
