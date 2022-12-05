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
    public function it_should_return_a_valid_authn_request_and_update_state_when_starting_on_login_flow()
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
        $this->assertSame('<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z" Destination="idp.nl/sso-url" AssertionConsumerServiceURL="sp.nl/consumer-url" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>sp.nl/consumer-url</saml:Issuer><samlp:Scoping ProxyCount="10"><samlp:RequesterID>https://sp.com/metadata</samlp:RequesterID></samlp:Scoping></samlp:AuthnRequest>
', $proxyRequest->getUnsignedXML());
        $this->assertSame('SAMLRequest=fVHfa8IwEH73ryh5r02FsRJsxenDBMfEdj7sRbL00LAm6XKJuP9%2BsbbDMfAtue%2FHfXc3nZ1VE53AojQ6J%2BmYklkxmiJXTcvm3h31Fr48oIsCTyPrgJx4q5nhKJFprgCZE6ycv6zZZExZa40zwjTkRnJfwRHBuhCARKtlTvbKiE%2Bo9wfQYLkLL1mTaDdkDIrAQ%2FSw0ui4dqFE0yymWZw%2BVjRjDxmb0HcSLUNsqbnrVLJux7pJEE3sbcg2H3oujEavwJZgT1LA23adE%2By4okeugk0%2F1pPUtdSH%2BxN9XEnInqtqE29ey4oU3VJZF9wW%2FztMk1u8v0ApTBt8Ls3P3wvjL8OmlAxwfxuwq2VxdK5FliTBWRiVKHC85o5fbf9Sh1rv%2Fvu%2FvXcx%2BgE%3D&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=DnIO65x5aFYLPS3QajHb59zKZzMyV0C4rGFnlqqozgaI3Pzw%2BFdbCithvPjsq%2F%2Be9r5tD%2FAzh4E9qjwFMJhZUCmPX3ctGw%2F2%2Fvv7fpGOvEE5Q%2FM77x9rMuO999OpLxqHhtAR%2FKJFVORyOSwVh87zABiSFOdOzIsKuVgZ%2BAsACc5gRZ7j4OmGOiw4SRhTUEqnJz5VURaKKTHt0Gig35GPnPbolDqqUk64Y3MYA3bHA5kl5coVBlfQsOnuN%2B3%2FvlN2bdtBvRTCAVWkjoviHf1KjD1lx3PA5OM6%2BNQ4333Kqu1O%2Fz2NCEzlEVMMA9qi%2BJBfveCa73ploDm06I9PtIaIQQ%3D%3D', $proxyRequest->buildRequestQuery());

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
    public function it_should_throw_an_exception_when_an_invalid_loa_is_requested_when_starting_on_login_flow()
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
    private function initGatewayService(array $idpConfiguration, array $spConfiguration, array $loaLevels, DateTime $now)
    {
        $session = new Session($this->sessionStorage);
        $this->stateHandler = new ProxyStateHandler($session, 'surfnet/gateway/request');
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
    private function mockRedirectBinding($samlResponseXml)
    {
        $authnRequest = ReceivedAuthnRequest::from($samlResponseXml);

        $this->redirectBinding->shouldReceive('receiveSignedAuthnRequestFrom')
            ->andReturn($authnRequest);
    }
}
