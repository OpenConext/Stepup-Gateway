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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Service\Gateway;

use DateTime;
use Mockery;
use SAML2\Response;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\FailedResponseService;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

final class FailedResponseServiceTest extends GatewaySamlTestCase
{
    private Mockery\Mock|FailedResponseService $gatewayFailedResponseService;
    private ResponseContext $responseContext;
    private Mockery\Mock|SamlEntityService $samlEntityService;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestStack = new RequestStack();

        $now = new \DateTime('@' . static::MOCK_TIMESTAMP);

        // init configuration
        $idpConfiguration = [
            'ssoUrl' => 'idp.nl/sso-url',
            'entityId' => 'idp.nl/entity-id',
            'privateKeys' => [
                $this->mockConfigurationPrivateKey('default', 'key.key'),
            ],
            'certificateFile' => $this->getKeyPath('/key.crt'),
        ];

        // init gateway service
        $this->initGatewayService($idpConfiguration, $now);
    }

    /**
     * @test
     */
    public function it_should_handle_send_loa_could_not_be_given_based_on_the_given_state_on_login_flow(): void
    {
        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://sp.com/acs', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://sp.com/metadata')
            ->andReturn($serviceProvider);

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
        ]);

        // Handle respond
        $response = $this->gatewayFailedResponseService->sendLoaCannotBeGiven($this->responseContext);

        // Assert response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('<?xml version="1.0" encoding="UTF-8"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z" InResponseTo="_123456789012345678901234567890123456789012"><saml:Issuer>idp.nl/entity-id</saml:Issuer><samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Responder"><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"/></samlp:StatusCode></samlp:Status></samlp:Response>
', $response->toUnsignedXML()->ownerDocument->saveXML());

        // Assert log
        $this->assertSame([
            'emergency' => [],
            'alert' => [],
            'critical' => [],
            'error' => [],
            'warning' => [],
            'notice' => [
                'Loa cannot be given, creating Response with NoAuthnContext status',
                'Responding to request "_123456789012345678901234567890123456789012" with response based on response from the remote IdP with response "_mocked_generated_id"',
            ],
            'info' => [],
            'debug' => [],
        ], $this->logger->getLogs());

        // Assert session
        $this->assertSame([
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
        ], $this->getSessionData('attributes'));
    }


    /**
     * @test
     */
    public function it_should_handle_authentication_cancelled_by_user_based_on_the_given_state_on_login_flow(): void
    {
        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://sp.com/acs', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://sp.com/metadata')
            ->andReturn($serviceProvider);

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
        ]);

        // Handle respond
        $response = $this->gatewayFailedResponseService->sendAuthenticationCancelledByUser($this->responseContext);

        // Assert response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('<?xml version="1.0" encoding="UTF-8"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z" InResponseTo="_123456789012345678901234567890123456789012"><saml:Issuer>idp.nl/entity-id</saml:Issuer><samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Responder"><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"/></samlp:StatusCode><samlp:StatusMessage>Authentication cancelled by user</samlp:StatusMessage></samlp:Status></samlp:Response>
', $response->toUnsignedXML()->ownerDocument->saveXML());

        // Assert log
        $this->assertSame([
            'emergency' => [],
            'alert' => [],
            'critical' => [],
            'error' => [],
            'warning' => [],
            'notice' => [
                'Authentication was cancelled by the user, creating Response with AuthnFailed status',
                'Responding to request "_123456789012345678901234567890123456789012" with response based on response from the remote IdP with response "_mocked_generated_id"',
            ],
            'info' => [],
            'debug' => [],
        ], $this->logger->getLogs());

        // Assert session
        $this->assertSame([
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
        ], $this->getSessionData('attributes'));
    }


    /**
     * @param DateTime $now
     */
    private function initGatewayService(array $idpConfiguration, DateTime $now): void
    {
        $session = new Session($this->sessionStorage);
        $requestStackMock = $this->createMock(RequestStack::class);
        $requestStackMock->method('getSession')->willReturn($session);

        $stateHandler = new ProxyStateHandler($requestStackMock, 'surfnet/gateway/request');
        $samlLogger = new SamlAuthenticationLogger($this->logger);

        $remoteIdp = new IdentityProvider($idpConfiguration);
        $responseBuilder = new ResponseBuilder();
        $this->samlEntityService = Mockery::mock(SamlEntityService::class);

        $this->responseContext = new ResponseContext(
            $remoteIdp,
            $this->samlEntityService,
            $stateHandler,
            $this->logger,
            $now
        );

        $this->gatewayFailedResponseService = new FailedResponseService(
            $samlLogger,
            $responseBuilder
        );
    }
}
