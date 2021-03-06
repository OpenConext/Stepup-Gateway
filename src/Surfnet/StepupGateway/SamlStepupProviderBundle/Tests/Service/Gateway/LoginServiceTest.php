<?php
/**
 * Copyright 2020 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Tests\Service\Gateway;

use DateTime;
use Mockery;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\NotConnectedServiceProviderException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ConnectedServiceProviders;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway\LoginService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;

class LoginServiceTest extends GatewaySamlTestCase
{
    /** @var Mockery\Mock|LoginService */
    private $samlProxyLoginService;

    /** @var Mockery\Mock|StateHandler */
    private $stateHandler;

    /** @var Mockery\Mock|PostBinding */
    private $postBinding;

    /** @var Mockery\Mock|RedirectBinding */
    private $redirectBinding;

    /** @var Mockery\Mock|SamlEntityService */
    private $samlEntityService;

    /** @var IdentityProvider */
    private $remoteIdp;

    /** @var IdentityProvider */
    private $idp;

    /** @var Provider */
    private $provider;

    public function setUp(): void
    {
        parent::setUp();

        // init configuration
        $idpConfiguration = [
            'ssoUrl' => 'idp.nl/sso-url',
            'entityId' => 'idp.nl/entity-id',
            'privateKeys' => [
                $this->mockConfigurationPrivateKey('default', 'key.key'),
            ],
            'certificateFile' => $this->getKeyPath('key.crt'),
        ];

        $remoteIdpConfiguration = [
            'ssoUrl' => 'remote-idp.nl/sso-url',
            'entityId' => 'remote-idp.nl/entity-id',
            'privateKeys' => [
                $this->mockConfigurationPrivateKey('default', 'key2.key'),
            ],
            'certificateFile' => $this->getKeyPath('key2.crt'),
        ];

        $spConfiguration = [
            'assertionConsumerUrl' => 'sp.nl/consumer-url',
            'entityId' => 'sp.nl/consumer-url',
            'privateKeys' => [
                $this->mockConfigurationPrivateKey('default', 'key3.key'),
            ],
        ];


        $connectedServiceProviders = [
            'https://gateway.tld/authentication/metadata',
        ];

        // init gateway service
        $this->initSamlProxyService($idpConfiguration, $remoteIdpConfiguration, $spConfiguration, $connectedServiceProviders);
    }

    /**
     * @test
     */
    public function it_should_return_a_valid_saml_response_and_update_state_when_the_registration_is_started_on_gssp_registration_flow()
    {
        // Create request
        $httpRequest = new Request();

        $authnRequest = '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                    ID="_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e"
                    Version="2.0"
                    IssueInstant="2015-04-17T13:57:52Z"
                    Destination="https://remote-idp.tld.nl/authentication/idp/single-sign-on"
                    AssertionConsumerServiceURL="https://gateway.tld/authentication/consume-assertion"
                    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
    <saml:Issuer>https://gateway.tld/authentication/metadata</saml:Issuer>
    <samlp:Scoping ProxyCount="10">
        <samlp:RequesterID>https://service-provider.example.org/authentication/metadata</samlp:RequesterID>
    </samlp:Scoping>
</samlp:AuthnRequest>';

        $this->mockRedirectBinding($authnRequest);

        $this->mockSessionData('__gssp_session', []);

        // Init request
        $proxyRequest = $this->samlProxyLoginService->singleSignOn($this->provider, $httpRequest);

        // Assert authnRequest
        $this->assertInstanceOf(AuthnRequest::class, $proxyRequest);
        $this->assertSame('idp.nl/sso-url', $proxyRequest->getDestination());
        $this->assertSame('<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z" Destination="idp.nl/sso-url" AssertionConsumerServiceURL="sp.nl/consumer-url" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>sp.nl/consumer-url</saml:Issuer><samlp:Scoping ProxyCount="10"><samlp:RequesterID>https://gateway.tld/authentication/metadata</samlp:RequesterID></samlp:Scoping></samlp:AuthnRequest>
', $proxyRequest->getUnsignedXML());
        $this->assertSame('SAMLRequest=fVJBbsIwELzzisj3JA5S1cgiQRQORaIqIimHXpCbrMBqYqfeDYXf14SkoqrEzd6Z8czuejI91ZV3BIvK6IRFAWfTdDRBWVeNmLV00Bv4agHJczyNogMS1lotjESFQssaUFAhstnLSowDLhpryBSmYjeS%2BwqJCJZcAOYtFwnb1ab4hHK3Bw1WkjupknnbIaNTOB5iC0uNJDW5Eo9in8d%2B9JjzWDzEYszfmbdwsZWW1KlU2QS6ChGN31qXbTZ4zo3GtgabgT2qAt42q4Rhxy165CpY9209KV0qvb%2Ff0ceVhOI5z9f%2B%2BjXLWdoNVXTBbfrfYRLe4v0GssI07p2L%2Bek8N%2B2l2YizAe53A3a5SA9EDYow3LuJfctzQFUZSrdA0KSKbghhDSRLSfJq9Vc%2B1HrH3%2FvtH0hHPw%3D%3D&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=NF54MMBUtA5VxO68xxa0Wzmst5wAbIlId%2FSABFaVa9dnBT2uZ4E6IduGTNVBZ0pHmpWb4QkVg9lIJmFZ3lQEbBkdOiDEOigspn09yfn4ugojKSA1R4%2BFfcegoTjKUC6Zh48C2HRKHCD%2F%2FSZziktIn7%2F91N4qJr%2FzL%2Blp4VPuFNX2pwQeMU4vKWGLDsUr2ibadDx5Xt8ZCINvsxtogBkn9UrVDZOWHqj4B3MfSQvGVKTYQ4evScK21lTu1X%2FJNLVALdXNDIdDTv8y6cDzofvSUdcxuQZevMnqvXeULT3l8HEDO4EYijsOImLLMrudBXzzrjibs8CfxKaMawFlS2bZqQ%3D%3D', $proxyRequest->buildRequestQuery());

        // Assert log
        $this->assertSame([
            'emergency' => [],
            'alert' => [],
            'critical' => [],
            'error' => [],
            'warning' => [],
            'notice' => [
                'AuthnRequest processing complete, received AuthnRequest from "https://gateway.tld/authentication/metadata", request ID: "_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e"',
                'Sending Proxy AuthnRequest with request ID: "_mocked_generated_id" for original AuthnRequest "_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e" to GSSP "testProvider" at "idp.nl/sso-url"',
            ],
            'info' => [],
            'debug' => [
                'Checking if SP "%s" is supported',
            ],
        ], $this->logger->getLogs());

        // Assert session
        $this->assertSame([
            'test_provider' => [
                'request_id' => '_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e',
                'service_provider' => 'https://gateway.tld/authentication/metadata',
                'assertion_consumer_service_url' => 'https://gateway.tld/authentication/consume-assertion',
                'relay_state' => '',
                'gateway_request_id' => '_mocked_generated_id',
            ],
        ], $this->getSessionData('attributes'));
    }


    /**
     * @test
     */
    public function it_should_throw_an_exception_when_a_sp_is_not_connected_when_the_registration_is_started_on_gssp_registration_flow()
    {
        $this->expectException(NotConnectedServiceProviderException::class);
        // Create request
        $httpRequest = new Request();

        $authnRequest = '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                    ID="_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e"
                    Version="2.0"
                    IssueInstant="2015-04-17T13:57:52Z"
                    Destination="https://remote-idp.tld.nl/authentication/idp/single-sign-on"
                    AssertionConsumerServiceURL="https://gateway.tld/authentication/consume-assertion"
                    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
    <saml:Issuer>https://unconnected-gateway.tld/authentication/metadata</saml:Issuer>
    <samlp:Scoping ProxyCount="10">
        <samlp:RequesterID>https://service-provider.example.org/authentication/metadata</samlp:RequesterID>
    </samlp:Scoping>
</samlp:AuthnRequest>';

        $this->mockRedirectBinding($authnRequest);

        $this->mockSessionData('__gssp_session', []);

        // Init request
        $this->samlProxyLoginService->singleSignOn($this->provider, $httpRequest);
    }

    /**
     * @param array $remoteIdpConfiguration
     * @param array $idpConfiguration
     * @param array $spConfiguration
     * @param array $connectedServiceProviders
     * @param DateTime $now
     */
    private function initSamlProxyService(array $remoteIdpConfiguration, array $idpConfiguration, array $spConfiguration, array $connectedServiceProviders)
    {
        $session = new Session($this->sessionStorage);
        $namespacedSessionBag = new NamespacedAttributeBag('__gssp_session');
        $session->registerBag($namespacedSessionBag);
        $this->stateHandler = new StateHandler($namespacedSessionBag, 'test_provider');
        $samlLogger = new SamlAuthenticationLogger($this->logger);

        $this->remoteIdp = new IdentityProvider($remoteIdpConfiguration);
        $this->idp = new IdentityProvider($idpConfiguration);
        $serviceProvider = new ServiceProvider($spConfiguration);
        $this->postBinding = Mockery::mock(PostBinding::class);
        $this->redirectBinding = Mockery::mock(RedirectBinding::class);
        $this->samlEntityService = Mockery::mock(SamlEntityService::class);
        $connectedServiceProviders = new ConnectedServiceProviders($this->samlEntityService, $connectedServiceProviders);

        $this->provider = new Provider(
            'testProvider',
            $this->idp,
            $serviceProvider,
            $this->remoteIdp,
            $this->stateHandler
        );

        $this->samlProxyLoginService = new LoginService(
            $samlLogger,
            $this->redirectBinding,
            $connectedServiceProviders
        );
    }

    /**
     * @param string $samlResponseXml
     */
    private function mockRedirectBinding($samlResponseXml)
    {
        $authnRequest = ReceivedAuthnRequest::from($samlResponseXml);

        $this->redirectBinding->shouldReceive('processSignedRequest')
            ->andReturn($authnRequest);
    }
}
