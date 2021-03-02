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
use Mockery;
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
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;

class SecondFactorVerificationServiceTest extends GatewaySamlTestCase
{
    /** @var Mockery\Mock|SecondFactorVerificationService */
    private $samlProxySecondFactorService;

    /** @var Mockery\Mock|StateHandler */
    private $stateHandler;

    /** @var ResponseContext */
    private $responseContext;

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

    /**
     * @test
     */
    public function it_should_return_a_valid_saml_response_and_update_state_when_the_verification_is_started_on_gssp_verification_flow() {

        $subjectNameId = 'test-gssp-id';

        $this->mockSessionData('__gssp_session', [
            'test_provider' => [
                'request_id' => '_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e',
                'service_provider' => 'https://gateway.tld/authentication/metadata',
                'assertion_consumer_service_url' => 'https://gateway.tld/authentication/consume-assertion',
                'relay_state' => '',
                'gateway_request_id' => '_mocked_generated_id',
            ],
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
        $this->assertSame('<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z" Destination="idp.nl/sso-url" AssertionConsumerServiceURL="sp.nl/consumer-url" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>sp.nl/consumer-url</saml:Issuer><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">test-gssp-id</saml:NameID></saml:Subject></samlp:AuthnRequest>
', $authnRequest->getUnsignedXML());
        $this->assertSame('SAMLRequest=fVLRTsIwFH3nK5a%2Bb2wkxqVhIygxkqASNn3whZT2gtWtnb0t8fNtumFIiLz13nNOz7k3dzr7aZvoCAalVgXJkpTMytEUWdt0dO7sh9rAtwO0kecppAEoiDOKaoYSqWItILWcVvOnFZ0kKe2MtprrhpxJrisYIhjrA5BouSjIttX8C8T2AAoMs%2F4lBYneThm9wvMQHSwVWqasb6VZHqd5nN3WaU5vcjpJ30m08LGlYjaopOgS1YwRdeyMzzY%2Fed5rha4FU4E5Sg6vm1VBMHD5gPSC9TDWnVRCqsP1iXY9CeljXa%2Fj9UtVkzIslYbgprx0mI7P8Z5cud0ncDtUz95muYgetGmZ%2Fd8%2FS7LQkSLeByp1Cjvgci9BkNL6rcQHxC6WYvDsPy6H6s90fHkF5egX&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=qDwcNcyzfju5TpJrVupTtxNH6aQSWaIN7KSeumLYQpVm79f8WSedytXBZcJ5LwjVO%2BeZeDpegyv0SOtA3TtkinMKigSmSGNcs3lRRy6eNg4W8tcgddGGRbiO4qLwPxmidHPzUF2NOhgAtiTR6Tdri8MaqwzOWBmh%2BdXtvIOkN7ntKACCahfhYZ3tnRGV8Tkln9pc6zO9471bwgl2kr6W91NMf%2Fjw9FxhRopasX8BPnEMipBdsHz7%2BS%2FagF12pJbImHcLgjqyDUSYYLmSVv%2FzRFZ3AxZmgdDHoiof1PPBLKFBKcFnavRKN9FgKLfeGIxV6AX9r6DtRFj45EC297pjUg%3D%3D', $authnRequest->buildRequestQuery());

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
            'test_provider' => [
                'request_id' => '_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e',
                'service_provider' => 'https://gateway.tld/authentication/metadata',
                'assertion_consumer_service_url' => 'https://gateway.tld/authentication/consume-assertion',
                'relay_state' => '',
                'gateway_request_id' => '_mocked_generated_id',
                'subject' => 'test-gssp-id',
                'response_context_service_id' => 'service_id',
                'is_second_factor_verification' => true,
            ],
        ], $this->getSessionData('attributes'));
    }


    /**
     * @param array $remoteIdpConfiguration
     * @param array $idpConfiguration
     * @param array $spConfiguration
     * @param array $connectedServiceProviders
     * @param DateTime $now
     */
    private function initSamlProxyService(array $remoteIdpConfiguration, array $idpConfiguration, array $spConfiguration, DateTime $now)
    {
        $session = new Session($this->sessionStorage);
        $namespacedSessionBag = new NamespacedAttributeBag('__gssp_session');
        $session->registerBag($namespacedSessionBag);
        $this->stateHandler = new StateHandler($namespacedSessionBag, 'test_provider');
        $samlLogger = new SamlAuthenticationLogger($this->logger);

        $this->remoteIdp = new IdentityProvider($remoteIdpConfiguration);
        $this->idp = new IdentityProvider($idpConfiguration);
        $serviceProvider = new ServiceProvider($spConfiguration);
        $this->samlEntityService = Mockery::mock(SamlEntityService::class);

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
