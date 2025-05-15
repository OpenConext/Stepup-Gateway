<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Test\Service\Gateway;

use DateTime;
use Mockery;
use Mockery\Mock;
use SAML2\Response;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\ProxyResponseFactory;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Exception\InvalidSecondFactorMethodException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Exception\ReceivedInvalidSubjectNameIdException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Saml\ResponseFactory;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\RespondService;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\ResponseValidator;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaAliasLookupService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

final class RespondServiceTest extends GatewaySamlTestCase
{
    /** @var Mock|RespondService */
    private $gatewayRespondService;

    /** @var Mock|ProxyStateHandler */
    private $stateHandler;

    /** @var ResponseContext */
    private $responseContext;

    /** @var LoaResolutionService */
    private $loaResolutionService;

    /** @var Mock|SamlEntityService */
    private $samlEntityService;

    /** @var IdentityProvider */
    private $hostedIdp;

    /** @var Mock|SecondFactorService */
    private $secondFactorService;

    /** @var ProxyResponseFactory */
    private $proxyResponseFactory;

    /** @var Mock&ResponseValidator */
    private $validator;

    public function setUp(): void
    {
        parent::setUp();

        $now = new \DateTime('@'.static::MOCK_TIMESTAMP);

        // init configuration
        $idpConfiguration = [
            'ssoUrl' => 'idp.nl/sso-url',
            'entityId' =>  'idp.nl/entity-id',
            'privateKeys' => [
                $this->mockConfigurationPrivateKey('default', 'key.key'),
            ],
            'certificateFile' => $this->getKeyPath('/key.crt'),
        ];

        $loaLevels = [
            [1, 'http://stepup.example.com/assurance/loa1'],
            [2, 'http://stepup.example.com/assurance/loa2'],
            [3, 'http://stepup.example.com/assurance/loa3'],
        ];

        $loaAliases = [
            'http://stepup.example.com/assurance/loa2' => 'http://suaas.example.com/assurance/loa2',
        ];

        // init gateway service
        $this->initGatewayLoginService($idpConfiguration, $loaLevels, $loaAliases, $now);
    }

    /**
     * @test
     */
    public function it_should_return_a_valid_saml_response_and_update_state_when_the_verification_is_succeeded_on_sfo_login_flow(): void {

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c',
            'surfnet/gateway/requestservice_provider' => 'https://gateway.tld/gssp/tiqr/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://gateway.tld/gssp/tiqr/consume-assertion',
            'surfnet/gateway/requestrelay_state' => '',
            'surfnet/gateway/requestresponse_controller' => 'SurfnetStepupGatewaySecondFactorOnlyBundle:SecondFactorOnly:respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'second_factor_only.response_context',
            'surfnet/gateway/requestname_id' => 'oom60v-3art',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
        ]);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://gateway.tld/gssp/tiqr/consume-assertion', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/gssp/tiqr/metadata')
            ->andReturn($serviceProvider);

        // Mock second factor
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->shouldReceive('getSecondFactorId')
            ->andReturn('mocked-second-factor-id');
        $secondFactor->shouldReceive('getDisplayLocale')
            ->andReturn('nl_NL');

        // Mock second factor service
        $this->secondFactorService->shouldReceive('findByUuid')
            ->with('mocked-second-factor-id', $this->responseContext)
            ->andReturn($secondFactor);

        $this->secondFactorService->shouldReceive('getLoaLevel')
            ->with($secondFactor)
            ->andReturn(new Loa(2.0, 'http://stepup.example.com/assurance/loa2'));

        // This should be done in the SecondFactorController on success
        $this->responseContext->saveSelectedSecondFactor($secondFactor);
        $this->responseContext->markSecondFactorVerified();

        $request = Mockery::mock(Request::class);

        $this->validator->shouldReceive('validate');
        // Handle respond
        $response = $this->gatewayRespondService->respond($this->responseContext, $request);

        // Assert response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('<?xml version="1.0" encoding="UTF-8"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z" InResponseTo="_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c"><saml:Issuer>idp.nl/entity-id</saml:Issuer><samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></samlp:Status><saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z"><saml:Issuer>idp.nl/entity-id</saml:Issuer><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
    <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
  <ds:Reference URI="#_mocked_generated_id"><ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></ds:Transforms><ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/><ds:DigestValue>M3WbI/sJb1QtagKJ4a8M8yKv8ukOFqNVP9Y28XW/pF4=</ds:DigestValue></ds:Reference></ds:SignedInfo><ds:SignatureValue>ZRzn0Z57uQgzhgprTxfzG2W/dXqCDXEM9xGMhHX6v7J4rwJblDhh1kahgUnk44VDt4Rd6rOX/W+dc1B3X8lO/aKQm50S0UKFUYmZVj62WKBcGxRuXdUKlzmpjcO035xE3wW6o14792hmBuQaXegN6u7IZntbvkGl7BLSnJ01qdUJeAbLIV7y3wrwMI41858q8gwe5p4gjGtJBNz4O8d1Fh25jVu3lAYzSmH/Y2txNPKrA/Ke/pRk9yRNpR2+wrqlTkmhBp6YxWKaeZA9CKnMP9k8tfVTrphBdhFkmwyUhqtUmysq4RLKQjxRl5Blt0pJGGzleYnmX0xl/05z+f4UJw==</ds:SignatureValue>
<ds:KeyInfo><ds:X509Data><ds:X509Certificate>MIID5zCCAs+gAwIBAgIJAL8E2GQ671hSMA0GCSqGSIb3DQEBCwUAMIGIMQswCQYDVQQGEwJOTDEQMA4GA1UECAwHWmVlbGFuZDETMBEGA1UEBwwKVmxpc3NpbmdlbjETMBEGA1UECgwKSWJ1aWxkaW5nczELMAkGA1UECwwCSVQxDzANBgNVBAMMBmlkcC5ubDEfMB0GCSqGSIb3DQEJARYQdGVzdEBleGFtcGxlLmNvbTAgFw0xODA4MjAwOTM2NDVaGA8yMTE4MDcyNzA5MzY0NVowgYgxCzAJBgNVBAYTAk5MMRAwDgYDVQQIDAdaZWVsYW5kMRMwEQYDVQQHDApWbGlzc2luZ2VuMRMwEQYDVQQKDApJYnVpbGRpbmdzMQswCQYDVQQLDAJJVDEPMA0GA1UEAwwGaWRwLm5sMR8wHQYJKoZIhvcNAQkBFhB0ZXN0QGV4YW1wbGUuY29tMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvmsJW2d+48UMs/r7BLQZWn4v7bXAQZxlAy5OE+SXZyafgiI22pJF1qRE6MiqYoVsJq4F7qfCv/8pFjUmnVIomaeRT88MI4nGrlEVL12SLzBzM/ftSrTP0FhoM8dmAW9VJUghjp7UYm7SFuPok0HpOV9A/5Z6nrkZ/mnEo24CDcFr5V06rg3fPofYD6FN1aIaYoNu3gtUo9rnS1fDw4m1fj5+X1VGKTqmvKpHTBS5cWZjvlU0Fw0N4tiJmJSq3sCclPvVXBXKcJeBhKA/jEurVTsqWerNfZ8O8iolwuXQLyva0ugvSRU8G0zIJMINUIAi03ulI978D1Pq0ZYIbcKxKwIDAQABo1AwTjAdBgNVHQ4EFgQUN6TQ5gwRg6ZFrjl8YuVssW59+RkwHwYDVR0jBBgwFoAUN6TQ5gwRg6ZFrjl8YuVssW59+RkwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQsFAAOCAQEARPxXmwswRaUg0jh6v9Q6IwOhPrPxw3uY1KxSX/+cfjPKVJXyfQQshJne4rAfLbBZPEmAbXi8xmMQvk7SjFsq8EjjGKCw9D5YikeucstxC6Ri4pRQcZcTi/o7Q06eKi2LFC7UM0RXIKBtCSUI5wYRzExFW0sUcTnfeCNdf0lk4fRVMrvccF04F7QANDcQSeMbdSSZZrUrEGYR+hGLypsq/7p5eSxgs8ooJhBgLULzOhfYz6qnCi5AHxvjKxogyvaDIdUvJUY/eU5xWpQT2IEW594tF876NNhnjPmZSZrGzRwkH2T0F9RZEf9sEjtd2tbbETjAzsBNNMOsGdLr2vO3WQ==</ds:X509Certificate></ds:X509Data></ds:KeyInfo></ds:Signature><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">oom60v-3art</saml:NameID><saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><saml:SubjectConfirmationData NotOnOrAfter="2018-08-17T09:03:20Z" InResponseTo="_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c"/></saml:SubjectConfirmation></saml:Subject><saml:Conditions NotBefore="2018-08-17T08:58:20Z" NotOnOrAfter="2018-08-17T09:03:20Z"><saml:AudienceRestriction><saml:Audience>https://gateway.tld/gssp/tiqr/metadata</saml:Audience></saml:AudienceRestriction></saml:Conditions><saml:AuthnStatement AuthnInstant="2018-08-17T08:58:20Z"><saml:AuthnContext><saml:AuthnContextClassRef>http://suaas.example.com/assurance/loa2</saml:AuthnContextClassRef><saml:AuthenticatingAuthority>idp.nl/entity-id</saml:AuthenticatingAuthority></saml:AuthnContext></saml:AuthnStatement></saml:Assertion></samlp:Response>
', $response->toUnsignedXML()->ownerDocument->saveXML());

        // Assert log
        $this->assertSame([
            'emergency' => [],
            'alert' => [],
            'critical' => [],
            'error' => [],
            'warning' => [],
            'notice' => [
                'Creating second-factor-only Response',
                'Responding to request "_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c" with newly created response "_mocked_generated_id"',
            ],
            'info' => [],
            'debug' => [],
        ], $this->logger->getLogs());

        // Assert session
        $this->assertSame([
            'surfnet/gateway/requestrequest_id' => '_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c',
            'surfnet/gateway/requestservice_provider' => 'https://gateway.tld/gssp/tiqr/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://gateway.tld/gssp/tiqr/consume-assertion',
            'surfnet/gateway/requestrelay_state' => '',
            'surfnet/gateway/requestresponse_controller' => 'SurfnetStepupGatewaySecondFactorOnlyBundle:SecondFactorOnly:respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'second_factor_only.response_context',
            'surfnet/gateway/requestname_id' => 'oom60v-3art',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestselected_second_factor' => 'mocked-second-factor-id',
            'surfnet/gateway/requestselected_second_factor_verified' => true,
            'surfnet/gateway/requestselected_second_factor_fallback' => false,
            'surfnet/gateway/requestlocale' => 'nl_NL',
        ], $this->getSessionData('attributes'));


        /** reset state */
        $this->gatewayRespondService->resetRespondState($this->responseContext);

        // Assert session
        $this->assertSame([
            'surfnet/gateway/requestrequest_id' => '_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c',
            'surfnet/gateway/requestservice_provider' => 'https://gateway.tld/gssp/tiqr/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://gateway.tld/gssp/tiqr/consume-assertion',
            'surfnet/gateway/requestrelay_state' => '',
            'surfnet/gateway/requestresponse_controller' => 'SurfnetStepupGatewaySecondFactorOnlyBundle:SecondFactorOnly:respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'second_factor_only.response_context',
            'surfnet/gateway/requestname_id' => 'oom60v-3art',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            // This is reset right after setting or not setting the SSO on 2FA cookie.
            'surfnet/gateway/requestselected_second_factor' => 'mocked-second-factor-id',
            'surfnet/gateway/requestselected_second_factor_verified' => false,
            'surfnet/gateway/requestselected_second_factor_fallback' => false,
            'surfnet/gateway/requestlocale' => 'nl_NL',
            'surfnet/gateway/requestsso_on_2fa_cookie_fingerprint' => ''
        ], $this->getSessionData('attributes'));
    }

    /**
     * @test
     */
    public function it_halts_execution_when_saml_response_is_invalid(): void
    {

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c',
            'surfnet/gateway/requestservice_provider' => 'https://gateway.tld/gssp/tiqr/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://gateway.tld/gssp/tiqr/consume-assertion',
            'surfnet/gateway/requestrelay_state' => '',
            'surfnet/gateway/requestresponse_controller' => 'SurfnetStepupGatewaySecondFactorOnlyBundle:SecondFactorOnly:respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'second_factor_only.response_context',
            'surfnet/gateway/requestname_id' => 'oom60v-3art',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
        ]);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://gateway.tld/gssp/tiqr/consume-assertion', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/gssp/tiqr/metadata')
            ->andReturn($serviceProvider);

        // Mock second factor
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->shouldReceive('getSecondFactorId')
            ->andReturn('mocked-second-factor-id');
        $secondFactor->shouldReceive('getDisplayLocale')
            ->andReturn('nl_NL');

        // Mock second factor service
        $this->secondFactorService->shouldReceive('findByUuid')
            ->with('mocked-second-factor-id', $this->responseContext)
            ->andReturn($secondFactor);

        $this->secondFactorService->shouldReceive('getLoaLevel')
            ->with($secondFactor)
            ->andReturn(new Loa(2.0, 'http://stepup.example.com/assurance/loa2'));

        // This should be done in the SecondFactorController on success
        $this->responseContext->saveSelectedSecondFactor($secondFactor);
        $this->responseContext->markSecondFactorVerified();

        $request = Mockery::mock(Request::class);

        $this->validator->shouldReceive('validate')->andThrow(new ReceivedInvalidSubjectNameIdException());
        // Handle respond
        self::expectException(ReceivedInvalidSubjectNameIdException::class);
        $this->gatewayRespondService->respond($this->responseContext, $request);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_second_factor_method_is_unknown_when_the_verification_is_succeeded_on_sfo_login_flow(): void
    {
        $this->expectException(InvalidSecondFactorMethodException::class);
        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c',
            'surfnet/gateway/requestservice_provider' => 'https://gateway.tld/gssp/tiqr/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://gateway.tld/gssp/tiqr/consume-assertion',
            'surfnet/gateway/requestrelay_state' => '',
            'surfnet/gateway/requestresponse_controller' => 'SurfnetStepupGatewaySecondFactorOnlyBundle:SecondFactorOnly:respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'second_factor_only.response_context',
            'surfnet/gateway/requestname_id' => 'oom60v-3art',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
        ]);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://gateway.tld/gssp/tiqr/consume-assertion', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/gssp/tiqr/metadata')
            ->andReturn($serviceProvider);

        // Mock second factor service
        $this->secondFactorService->shouldReceive('findByUuid')
            ->with('mocked-second-factor-id')
            ->andReturn(null);

        $request = Mockery::mock(Request::class);

        // Handle respond
        $this->gatewayRespondService->respond($this->responseContext, $request);
    }


    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_second_factor_is_not_verified_when_the_verification_is_succeeded_on_sfo_login_flow(): void
    {
        $this->expectException(InvalidSecondFactorMethodException::class);
        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c',
            'surfnet/gateway/requestservice_provider' => 'https://gateway.tld/gssp/tiqr/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://gateway.tld/gssp/tiqr/consume-assertion',
            'surfnet/gateway/requestrelay_state' => '',
            'surfnet/gateway/requestresponse_controller' => 'SurfnetStepupGatewaySecondFactorOnlyBundle:SecondFactorOnly:respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'second_factor_only.response_context',
            'surfnet/gateway/requestname_id' => 'oom60v-3art',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
        ]);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://gateway.tld/gssp/tiqr/consume-assertion', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/gssp/tiqr/metadata')
            ->andReturn($serviceProvider);

        // Mock second factor
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->shouldReceive('getSecondFactorId')
            ->andReturn('mocked-second-factor-id');
        $secondFactor->shouldReceive('getDisplayLocale')
            ->andReturn('nl_NL');

        // Mock second factor service
        $this->secondFactorService->shouldReceive('findByUuid')
            ->with('mocked-second-factor-id')
            ->andReturn($secondFactor);

        $this->secondFactorService->shouldReceive('getLoaLevel')
            ->with($secondFactor)
            ->andReturn(new Loa(2.0, 'http://stepup.example.com/assurance/loa2'));

        // This should be done in the SecondFactorController on success
        $this->responseContext->saveSelectedSecondFactor($secondFactor);

        $request = Mockery::mock(Request::class);

        // Handle respond
        $this->gatewayRespondService->respond($this->responseContext, $request);
    }

    /**
     * @param array $idpConfiguration
     * @param array $loaAliases
     * @param array $loaLevels
     * @param DateTime $now
     * @param array $sessionData
     */
    private function initGatewayLoginService(array $idpConfiguration, array $loaLevels,  array $loaAliases, DateTime $now): void
    {
        $samlLogger = new SamlAuthenticationLogger($this->logger);
        $session = new Session($this->sessionStorage);
        $requestStackMock = $this->createMock(RequestStack::class);
        $requestStackMock->method('getSession')->willReturn($session);

        $this->stateHandler = new ProxyStateHandler($requestStackMock, 'surfnet/gateway/request');

        $this->hostedIdp = new IdentityProvider($idpConfiguration);

        $this->loaResolutionService = $this->mockLoaResolutionService($loaLevels);
        $this->samlEntityService = Mockery::mock(SamlEntityService::class);
        $this->secondFactorService = Mockery::mock(SecondFactorService::class);
        $secondFactorTypeService = Mockery::mock(SecondFactorTypeService::class);

        $this->responseContext = new ResponseContext(
            $this->hostedIdp,
            $this->samlEntityService,
            $this->stateHandler,
            $this->logger,
            $now
        );

        $assertionSigningService = new AssertionSigningService($this->hostedIdp);
        $this->proxyResponseFactory = new ResponseFactory($this->hostedIdp, $this->stateHandler, $assertionSigningService, $now);
        $loaAliasLookup = new LoaAliasLookupService($loaAliases);

        $this->validator = Mockery::mock(ResponseValidator::class);

        $this->gatewayRespondService = new RespondService(
            $samlLogger,
            $this->loaResolutionService,
            $loaAliasLookup,
            $this->proxyResponseFactory,
            $this->secondFactorService,
            $secondFactorTypeService,
            $this->validator
        );
    }

    /**
     * @param array $loaLevels
     * @return LoaResolutionService
     */
    private function mockLoaResolutionService(array $loaLevels)
    {
        $loaLevelObjects = [];
        foreach ($loaLevels as $level) {
            $loaLevelObjects[] = new Loa($level[0], $level[1]);
        }
        return new LoaResolutionService($loaLevelObjects);
    }
}
