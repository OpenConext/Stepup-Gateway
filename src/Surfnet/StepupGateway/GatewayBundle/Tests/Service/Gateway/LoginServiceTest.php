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
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Exception\RequesterFailureException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\LoginService;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

final class LoginServiceTest extends GatewaySamlTestCase
{
    /** @var Mockery\Mock|LoginService */
    private $gatewayLoginService;

    /** @var Mockery\Mock|ProxyStateHandler */
    private $stateHandler;

    /** @var ResponseContext */
    private $responseContext;

    /** @var Mockery\Mock|LoaResolutionService */
    private $loaResolutionService;

    /** @var Mockery\Mock|PostBinding */
    private $redirectBinding;

    /** @var Mockery\Mock|SamlEntityService */
    private $samlEntityService;

    /** @var IdentityProvider */
    private $remoteIdp;

    /** @var Mockery\Mock|SecondFactorService */
    private $secondFactorService;

    public function setUp(): void
    {
        parent::setUp();

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

        $spConfiguration = [
            'assertionConsumerUrl' => 'sp.nl/consumer-url',
            'entityId' => 'sp.nl/consumer-url',
            'privateKeys' => [
                $this->mockConfigurationPrivateKey('default', 'key2.key'),
            ],
        ];

        $loaLevels = [
            [1, 'http://stepup.example.com/assurance/loa1'],
            [2, 'http://stepup.example.com/assurance/loa2'],
            [3, 'http://stepup.example.com/assurance/loa3'],
        ];

        // init gateway service
        $this->initGatewayService($idpConfiguration, $spConfiguration, $loaLevels, $now);
    }

    /**
     * @test
     */
    public function it_should_return_a_valid_authn_request_and_update_state_when_starting_on_login_flow(): void
    {
        // Create request
        $httpRequest = new Request([AuthnRequest::PARAMETER_RELAY_STATE => 'relay_state']);

        $originalRequest = '<samlp:AuthnRequest
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="_123456789012345678901234567890123456789012"
    Version="2.0"
    IssueInstant="2014-10-22T11:06:59Z"
    Destination="https://gateway.org/sso"
    AssertionConsumerServiceURL="https://sp.com/acs"
    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://sp.com/metadata</saml:Issuer>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://stepup.example.com/assurance/loa2</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>';

        $this->mockSessionData('_sf2_attributes', []);

        $this->mockRedirectBinding($originalRequest);

        // Init request
        $proxyRequest = $this->gatewayLoginService->singleSignOn($httpRequest);

        // Assert authnRequest
        $this->assertInstanceOf(AuthnRequest::class, $proxyRequest);
        $this->assertSame('idp.nl/sso-url', $proxyRequest->getDestination());
        $this->assertSame('<?xml version="1.0" encoding="UTF-8"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z" Destination="idp.nl/sso-url" AssertionConsumerServiceURL="sp.nl/consumer-url" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>sp.nl/consumer-url</saml:Issuer><samlp:Scoping ProxyCount="10"><samlp:RequesterID>https://sp.com/metadata</samlp:RequesterID></samlp:Scoping></samlp:AuthnRequest>
', $proxyRequest->getUnsignedXML());
        $this->assertSame('SAMLRequest=fVHda8IwEH%2FfX1HyXpsKYyXYilPGCo6JrXvYi2TpoWFN0uVScf%2F9Yj%2BGY%2BBbcr%2F7fdzdbH5WdXACi9LolMQTSgLQwlRSH1KyK5%2FChMyzuxlyVTds0bqj3sJXC%2BgCT9TIOiAlrdXMcJTINFeAzAlWLF7WbDqhrLHGGWFqckW5zeCIYJ1PRIJ8lZK9MuITqv0BNFju%2FEtWJHgbQ08voXPEFnKNjmvnSzROQpqE8UNJE3afsCl9J8HKx5aau44lq2ai6wjRhK312Raj59JobBXYAuxJCtht1ynBrlcMSE%2FYDGM9St1v69ZEH30Tsuey3ISb16IkWbdU1gW32X%2BHWXSNDxcohGm8zsX8%2FL007WXYmJIRHm4DNl9lR%2BcaZFHklYVRkQLHK%2B54L%2Fu3dawN6r%2F%2F63tndz8%3D&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=Ec6AfwzuFHXoV0MjuBCR8yBOSc2Gi10ezlCWI2%2FCcAK%2FR2HnI4Jx%2BFsWB4PTt1As%2BrICTefawIpJG3NsoskSVjjHpazmD8xde1lwuNAVzV0BrOFW7Z78UKhRv5j4wielT0xSI1NsoyGh8dp34WsyahwuQN6b2klzyFq%2FcBgPL1kAiOBY%2BL89isQI9B9dmUG%2FokbBlHO3WMmoj%2FDWxFDiweD1ArwNKoeklEznEyclI7BZJBP4KsjxaerLbuoZ7g5Pmc5a92AaO%2BUL%2FgbcZbu9euPnCDxP3F%2BAVSvHED88hojBUNmFTGOKC0GnPpBQQdazHC%2FiurQPjpxlytxm3M2ipQ%3D%3D', $proxyRequest->buildRequestQuery());

        // Assert log
        $this->assertSame([
            'emergency' => [],
            'alert' => [],
            'critical' => [],
            'error' => [],
            'warning' => [],
            'notice' => [
                'AuthnRequest processing complete, received AuthnRequest from "https://sp.com/metadata", request ID: "_123456789012345678901234567890123456789012"',
                'Sending Proxy AuthnRequest with request ID: "_mocked_generated_id" for original AuthnRequest "_123456789012345678901234567890123456789012"',
            ],
            'info' => [],
            'debug' => [],
        ], $this->logger->getLogs());

        // Assert session
        $this->assertSame([
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
            'surfnet/gateway/requestrelay_state' => 'relay_state',
            'surfnet/gateway/requestforce_authn' => false,
            'surfnet/gateway/requestresponse_controller' => 'SurfnetStepupGatewayGatewayBundle:Gateway:respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'gateway.proxy.response_context',
            'surfnet/gateway/auth_mode/_123456789012345678901234567890123456789012' => 'sso',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestgateway_request_id' => '_mocked_generated_id',
        ], $this->getSessionData('attributes'));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_an_invalid_loa_is_requested_when_starting_on_login_flow(): void
    {
        $this->expectException(RequesterFailureException::class);
        // Create request
        $httpRequest = new Request([AuthnRequest::PARAMETER_RELAY_STATE => 'relay_state']);

        $originalRequest = '<samlp:AuthnRequest
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="_123456789012345678901234567890123456789012"
    Version="2.0"
    IssueInstant="2014-10-22T11:06:59Z"
    Destination="https://gateway.org/sso"
    AssertionConsumerServiceURL="https://sp.com/acs"
    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://sp.com/metadata</saml:Issuer>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://stepup.example.com/assurance/invalid-loa</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>';

        $this->mockSessionData('_sf2_attributes', []);

        $this->mockRedirectBinding($originalRequest);

        // Init request
        $this->gatewayLoginService->singleSignOn($httpRequest);
    }


    /**
     * @param array $idpConfiguration
     * @param array $spConfiguration
     * @param array $dictionaryAttributes
     * @param array $loaLevels
     * @param int $now
     * @param array $sessionData
     */
    private function initGatewayService(array $idpConfiguration, array $spConfiguration, array $loaLevels, DateTime $now): void
    {
        $session = new Session($this->sessionStorage);
        $requestStackMock = $this->createMock(RequestStack::class);
        $requestStackMock->method('getSession')->willReturn($session);

        $this->stateHandler = new ProxyStateHandler($requestStackMock, 'surfnet/gateway/request');
        $samlLogger = new SamlAuthenticationLogger($this->logger);

        $hostedServiceProvider = new ServiceProvider($spConfiguration);
        $this->remoteIdp = new IdentityProvider($idpConfiguration);
        $this->loaResolutionService = $this->mockLoaResolutionService($loaLevels);
        $this->redirectBinding = Mockery::mock(RedirectBinding::class);
        $this->samlEntityService = Mockery::mock(SamlEntityService::class);
        $this->secondFactorService = Mockery::mock(SecondFactorService::class);

        $this->responseContext = new ResponseContext(
            $this->remoteIdp,
            $this->samlEntityService,
            $this->stateHandler,
            $this->logger,
            $now
        );

        $this->gatewayLoginService = new LoginService(
            $samlLogger,
            $this->stateHandler,
            $this->loaResolutionService,
            $hostedServiceProvider,
            $this->remoteIdp,
            $this->redirectBinding
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

    /**
     * @param string $samlResponseXml
     */
    private function mockRedirectBinding($samlResponseXml): void
    {
        $authnRequest = ReceivedAuthnRequest::from($samlResponseXml);

        $this->redirectBinding->shouldReceive('receiveSignedAuthnRequestFrom')
            ->andReturn($authnRequest);
    }
}
