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
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\DateTime\ExpirationHelperInterface;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Http\CookieHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\Configuration;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\NullCookieValue;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration test
 */
class CookieServiceTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

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

    /**
     * @var ServiceProvider&Mockery\Mock
     */
    private $sp;
    /**
     * @var Mockery\Mock|(Mockery\MockInterface&LoggerInterface)
     */
    private $logger;

    protected function buildService(Configuration $configuration, bool $shouldIgnoreLogs = true): void
    {
        // Not all dependencies are included for real, the ones not focussed on crypto and cookie storage are mocked
        $this->logger = Mockery::mock(LoggerInterface::class);
        if ($shouldIgnoreLogs) {
            $this->logger->shouldIgnoreMissing();
        }
        $this->institutionService = Mockery::mock(InstitutionConfigurationService::class);
        $this->secondFactorService = Mockery::mock(SecondFactorService::class);
        $this->secondFactorTypeService = Mockery::mock(SecondFactorTypeService::class);
        $this->configuration = $configuration;
        $this->encryptionHelper = new HaliteCryptoHelper($configuration);
        $this->expirationHelper = Mockery::mock(ExpirationHelperInterface::class);
        $cookieHelper = new CookieHelper($this->configuration, $this->encryptionHelper, $this->logger);
        $this->service = new CookieService(
            $cookieHelper,
            $this->institutionService,
            $this->secondFactorService,
            $this->secondFactorTypeService,
            $this->expirationHelper,
            $this->logger
        );

        $this->responseContext = Mockery::mock(ResponseContext::class);
        $this->responseContext
            ->shouldReceive('isForceAuthn')
            ->andReturnFalse();

        $this->sp = Mockery::mock(ServiceProvider::class);
        $this->sp
            ->shouldReceive('getEntityId')
            ->andReturn('https://remote.sp.stepup.example.com');
        $this->sp
            ->shouldReceive('allowedToSetSsoCookieOn2fa')
            ->andReturnTrue();
        $this->sp
            ->shouldReceive('allowSsoOn2fa')
            ->andReturnTrue();
        $this->responseContext
            ->shouldReceive('getServiceProvider')
            ->andReturn($this->sp);
    }

    public function test_check_preconditions_happy()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            ),
            false
        );
        self::assertTrue($this->service->preconditionsAreMet($this->responseContext));
    }

    public function test_check_preconditions_is_force_authn()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            ),
            false
        );
        $this->responseContext = Mockery::mock(ResponseContext::class);
        $this->responseContext
            ->shouldReceive('isForceAuthn')
            ->andReturnTrue();
        $this->logger
            ->shouldReceive('notice')
            ->with('Ignoring SSO on 2FA cookie when ForceAuthN is specified.');

        self::assertFalse($this->service->preconditionsAreMet($this->responseContext));
    }

    public function test_check_preconditions_is_remote_sp_disabled()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            ),
            false
        );
        $this->sp = Mockery::mock(ServiceProvider::class);
        $this->sp
            ->shouldReceive('allowSsoOn2fa')
            ->andReturnFalse();
        $this->sp
            ->shouldReceive('getEntityId')
            ->andReturn('https://remote.sp.stepup.example.com');
        $this->responseContext = Mockery::mock(ResponseContext::class);
        $this->responseContext
            ->shouldReceive('isForceAuthn')
            ->andReturnFalse();
        $this->responseContext
            ->shouldReceive('getServiceProvider')
            ->andReturn($this->sp);

        $this->logger
            ->shouldReceive('notice')
            ->with('Ignoring SSO on 2FA for SP: https://remote.sp.stepup.example.com');

        self::assertFalse($this->service->preconditionsAreMet($this->responseContext));
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
        $this->responseContext
            ->shouldReceive('isVerifiedBySsoOn2faCookie')
            ->andReturn(false);

        $response = $this->service->handleSsoOn2faCookieStorage($this->responseContext, $request, $response);

        $cookieJar = $response->headers->getCookies();
        self::assertCount(1, $cookieJar);
        $cookie = reset($cookieJar);
        // The name and lifetime of the cookie should match the one we configured it to be
        self::assertEquals($this->configuration->getName(), $cookie->getName());
        self::assertEquals($this->configuration->getLifetime(), $cookie->getExpiresTime());
        // By default we set same-site header to none
        self::assertEquals(Cookie::SAMESITE_NONE, $cookie->getSameSite());
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
        $this->responseContext
            ->shouldReceive('isVerifiedBySsoOn2faCookie')
            ->andReturn(false);

        $response = $this->service->handleSsoOn2faCookieStorage($this->responseContext, $request, $response);

        $cookieJar = $response->headers->getCookies();
        self::assertCount(1, $cookieJar);
        $cookie = reset($cookieJar);
        // The name and lifetime of the cookie should match the one we configured it to be
        self::assertEquals($this->configuration->getName(), $cookie->getName());
        self::assertEquals(time() + $this->configuration->getLifetime(), $cookie->getExpiresTime());
    }

    public function test_storing_a_session_cookie_new_authentication()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            )
        );
        $response = new Response('<html lang="en"><body><h1>hi</h1></body></html>', 200);
        $request = Mockery::mock(Request::class);
        $sfMock = Mockery::mock(SecondFactor::class)->makePartial();
        $sfMock->secondFactorId = 'sf-id-1234';
        $sfMock->institution = 'institution-a';
        $sfMock->identityId = 'james-hoffman';
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
            ->andReturn(1.5);
        $this->responseContext
            ->shouldReceive('isVerifiedBySsoOn2faCookie')
            ->andReturn(false);

        $response = $this->service->handleSsoOn2faCookieStorage($this->responseContext, $request, $response);

        $cookieJar = $response->headers->getCookies();
        self::assertCount(1, $cookieJar);
        $cookie = reset($cookieJar);
        // The name and lifetime of the cookie should match the one we configured it to be
        self::assertEquals($this->configuration->getName(), $cookie->getName());
        self::assertEquals($this->configuration->getLifetime(), $cookie->getExpiresTime());
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
            ),
            false
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

        // For: #186011523 verify a clear log message is stating we are not storing
        // SSO cookie because the SP is not allowing it.
        $this->logger->shouldReceive('notice')->with('SP: https://ra.stepup.example.com/gssf/tiqr/metadata does not allow writing SSO on 2FA cookies');

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
        $yubikey = $this->buildSecondFactor(3.0, 'abcdef-1234');
        $cookieValue = $this->cookieValue();

        $this->expirationHelper
            ->shouldReceive('isExpired')
            ->andReturn(false);

        $this->secondFactorService
            ->shouldReceive('findByUuid')
            ->with('abcdef-1234')
            ->andReturn($yubikey);

        self::assertTrue(
            $this->service->maySkipAuthentication(
                3.0,
                'ident-1234',
                $cookieValue
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

        self::assertFalse(
            $this->service->maySkipAuthentication(
                3.0,
                'abcdef-1234',
                Mockery::mock(NullCookieValue::class)
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
            ),
            false
        );
        $cookieValue = $this->cookieValue();

        $this->logger
            ->shouldReceive('notice')
            ->with('The required LoA 4 did not match the LoA of the SSO cookie LoA 3');

        self::assertFalse(
            $this->service->maySkipAuthentication(
                4.0, // LoA required by SP is 4.0, the one in the cookie is 3.0
                'abcdef-1234',
                $cookieValue
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
            ),
            false
        );
        $cookieValue = $this->cookieValue();

        $this->logger
            ->shouldReceive('notice')
            ->with('The SSO on 2FA cookie was not issued to Jane Doe, but to ident-1234');

        self::assertFalse(
            $this->service->maySkipAuthentication(
                2.0,
                'Jane Doe', // Not issued to Jane Doe but to abcdef-1234
                $cookieValue
            )
        );
    }

    public function test_skipping_authentication_fails_when_token_expired()
    {
        $this->buildService(
            new Configuration(
                'test-cookie',
                'session',
                0,
                '0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f'
            ),
            false
        );

        $cookieValue = $this->cookieValue();

        $this->expirationHelper
            ->shouldReceive('isExpired')
            ->andReturn(true);

        $this->logger
            ->shouldReceive('notice')
            ->with('The SSO on 2FA cookie has expired. Meaning [authentication time] + [cookie lifetime] is in the past');

        self::assertFalse(
            $this->service->maySkipAuthentication(
                3.0,
                'ident-1234',
                $cookieValue
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
            ),
            false
        );

        $cookieValue = $this->cookieValue();

        $this->expirationHelper
            ->shouldReceive('isExpired')
            ->andReturn(false);

        // When the token cannot be found e.g. token was revoked in the meantime
        $this->secondFactorService
            ->shouldReceive('findByUuid')
            ->andReturnNull();

        $this->logger->shouldReceive('notice')->with(
            'The second factor stored in the SSO cookie was revoked or has otherwise became unknown to Gateway',
            ['secondFactorIdFromCookie' => 'abcdef-1234']
        );
        self::assertFalse(
            $this->service->maySkipAuthentication(
                3.0,
                'ident-1234',
                $cookieValue
            )
        );
    }

    private function cookieValue(): CookieValue
    {
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->secondFactorId = 'abcdef-1234';
        $secondFactor->secondFactorIdentifier = 'identifier-abcdef-1234';
        $secondFactor->identityId = 'ident-1234';
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
        $token->secondFactorIdentifier = 'identifier-' . $identifier;
        $token->displayLocale = 'nl';
        $token->shouldReceive('getLoaLevel')->andReturn($loaLevel);
        return $token;
    }
}
