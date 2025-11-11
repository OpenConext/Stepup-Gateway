<?php
/**
 * Copyright 2018 SURFnet B.V.
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
use Mockery as m;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway\SecondFactorVerificationService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;

class SecondFactorVerificationServiceTest extends GatewaySamlTestCase
{
    /** @var m::Mock|SecondFactorVerificationService */
    private $samlProxySecondFactorService;

    /** @var m::Mock|StateHandler */
    private $stateHandler;

    /** @var ResponseContext */
    private $responseContext;

    /** @var m::Mock|SamlEntityService */
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

        $now = new \DateTime('@' . static::MOCK_TIMESTAMP);

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

        // init gateway service
        $this->initSamlProxyService($idpConfiguration, $remoteIdpConfiguration, $spConfiguration, $now);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_should_return_a_valid_saml_response_and_update_state_when_the_verification_is_started_on_gssp_verification_flow(): void {

        $subjectNameId = 'test-gssp-id';

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/gssp/test_provider/request_id' => '_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e',
            'surfnet/gateway/gssp/test_provider/service_provider' => 'https://gateway.tld/authentication/metadata',
            'surfnet/gateway/gssp/test_provider/assertion_consumer_service_url' => 'https://gateway.tld/authentication/consume-assertion',
            'surfnet/gateway/gssp/test_provider/relay_state' => '',
            'surfnet/gateway/gssp/test_provider/gateway_request_id' => '_mocked_generated_id',
        ]);

        // Handle request
        $authnRequest = $this->samlProxySecondFactorService->sendSecondFactorVerificationAuthnRequest(
            $this->provider,
            $subjectNameId,
            'service_id'
        );

        // Assert authnRequest
        $this->assertInstanceOf(AuthnRequest::class, $authnRequest);
        $this->assertSame('idp.nl/sso-url', $authnRequest->getDestination());
        $this->assertSame('<?xml version="1.0" encoding="UTF-8"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z" Destination="idp.nl/sso-url" AssertionConsumerServiceURL="sp.nl/consumer-url" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>sp.nl/consumer-url</saml:Issuer><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">test-gssp-id</saml:NameID></saml:Subject></samlp:AuthnRequest>
', $authnRequest->getUnsignedXML());
        $this->assertSame('SAMLRequest=fVLRTsIwFH3nK5a%2Bd2wkxqVhIyghLkElbvjgCyndBatbO3tb4udbt2FIiLz13nNOz7k3dzr7burgCAalVimJw4gEoISupDqkZFMuaUJm2WiKvKlbNnf2Xb3AlwO0gRcqZB2QEmcU0xwlMsUbQGYFK%2BaPKzYJI9YabbXQNTmTXFdwRDDWJyJBvkjJttHiE6rtARQYbv1LViR4PYWe%2FIbOER3kCi1X1reiOKFRQuPbMkrYTcIm0RsJFj62VNx2Klm1oarHiJo647PNT573WqFrwBRgjlLA5mWVEuy4YkB6wXoY606qflvXJtr1JGQPZbmm6%2BeiJFm3VNYFN9mlw3R8jvfkwu0%2BQNihevI2%2BSJYatNw%2B79%2FHMZdR1Z031GZU9iCkHsJFcms3wo9ILZUVoNn%2F3E2VH%2Bm48sryEY%2F&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=P7i67MJjBaXTM4UVtX80kNsi8DylAqriOUQxqIjTA6eR2X8Vpgrr9DtJOC3jlsJdMTSS4AJTpRsvOgzlzhpbP%2BXzn%2F7wDkUkaOwL7AkkM8sKkzcV9n00StB%2BT2mEKzoYD26jGxWtLD4ux6CE5vmFbmGHQB7VotqvYzXebB%2F6pT2Puc67beIrMKIx9Hl3%2B0ht424tdiN0FpSdgqI1Dac7lMqBXssXCzDpyF66iIs98jpazqdN%2FsVYbQd9ihCxcXDLdmSO4P6AXoO3nmlY1nnGUxy8y1gYqzYfQ7pe7KVHKBAJX32wfMmdxcNeYwy3hvDTLwA49tsHJ8kWtRv7ZnF6Xw%3D%3D', $authnRequest->buildRequestQuery());

        // Assert log
        $this->assertSame([
            'emergency' => [],
            'alert' => [],
            'critical' => [],
            'error' => [],
            'warning' => [],
            'notice' => [
                'Sending AuthnRequest to verify Second Factor with request ID: "_mocked_generated_id" to GSSP "testProvider" at "idp.nl/sso-url" for subject "test-gssp-id"',
            ],
            'info' => [],
            'debug' => [],
        ], $this->logger->getLogs());

        // Assert session
        $this->assertSame([
            'surfnet/gateway/gssp/test_provider/request_id' => '_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e',
            'surfnet/gateway/gssp/test_provider/service_provider' => 'https://gateway.tld/authentication/metadata',
            'surfnet/gateway/gssp/test_provider/assertion_consumer_service_url' => 'https://gateway.tld/authentication/consume-assertion',
            'surfnet/gateway/gssp/test_provider/relay_state' => '',
            'surfnet/gateway/gssp/test_provider/gateway_request_id' => '_mocked_generated_id',
            'surfnet/gateway/gssp/test_provider/subject' => 'test-gssp-id',
            'surfnet/gateway/gssp/test_provider/response_context_service_id' => 'service_id',
            'surfnet/gateway/gssp/test_provider/is_second_factor_verification' => true,
        ], $this->getSessionData('attributes'));
    }


    /**
     * @param array $remoteIdpConfiguration
     * @param array $idpConfiguration
     * @param array $spConfiguration
     * @param array $connectedServiceProviders
     * @param DateTime $now
     */
    private function initSamlProxyService(array $remoteIdpConfiguration, array $idpConfiguration, array $spConfiguration, DateTime $now): void
    {
        $session = new Session($this->sessionStorage);
        $requestStack = m::mock(RequestStack::class);
        $requestStack->shouldReceive('getSession')->andReturn($session);
        $this->stateHandler = new StateHandler($requestStack, 'test_provider');
        $samlLogger = new SamlAuthenticationLogger($this->logger);

        $this->remoteIdp = new IdentityProvider($remoteIdpConfiguration);
        $this->idp = new IdentityProvider($idpConfiguration);
        $serviceProvider = new ServiceProvider($spConfiguration);
        $this->samlEntityService = m::mock(SamlEntityService::class);

        $this->provider = new Provider(
            'testProvider',
            $this->idp,
            $serviceProvider,
            $this->remoteIdp,
            $this->stateHandler
        );

        $this->responseContext = new ResponseContext(
            $this->remoteIdp,
            $this->samlEntityService,
            $this->stateHandler,
            $this->logger,
            $now
        );

        $this->samlProxySecondFactorService = new SecondFactorVerificationService(
            $samlLogger,
            $this->responseContext,
            $this->responseContext
        );
    }
}
