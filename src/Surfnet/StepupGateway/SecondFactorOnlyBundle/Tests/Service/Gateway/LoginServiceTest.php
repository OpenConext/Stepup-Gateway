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
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\HttpBindingFactory;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Exception\RequesterFailureException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\LoginService;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaAliasLookupService;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaResolutionService as SecondFactorLoaResolutionService;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\SecondFactorOnlyNameIdValidationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

final class LoginServiceTest extends GatewaySamlTestCase
{
    /** @var Mockery\Mock|LoginService */
    private $gatewayLoginService;

    /** @var Mockery\Mock|ProxyStateHandler */
    private $stateHandler;

    /** @var Mockery\Mock|LoaResolutionService */
    private $loaResolutionService;

    /** @var Mockery\Mock|PostBinding */
    private $postBinding;

    /** @var Mockery\Mock|RedirectBinding */
    private $redirectBinding;

    /** @var Mockery\Mock|SamlEntityService */
    private $samlEntityService;

    public function setUp(): void
    {
        parent::setUp();

        $loaLevels = [
            [1, 'http://stepup.example.com/assurance/loa1'],
            [2, 'http://stepup.example.com/assurance/loa2'],
            [3, 'http://stepup.example.com/assurance/loa3'],
        ];

        $loaAliases = [
            'http://stepup.example.com/assurance/loa2' => 'http://suaas.example.com/assurance/loa2',
        ];

        // init gateway service
        $this->initGatewayLoginService($loaLevels, $loaAliases);
    }

    /**
     * @test
     */
    public function it_should_return_a_valid_saml_response_and_update_state_when_starting_verification_on_sfo_login_flow()
    {
        // Create request
        $httpRequest = new Request();
        $originalRequest = ReceivedAuthnRequest::from('<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                    ID="_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c"
                    Version="2.0"
                    IssueInstant="2017-04-18T16:35:32Z"
                    Destination="https://tiqr.tld/saml/sso"
                    AssertionConsumerServiceURL="https://gateway.tld/gssp/tiqr/consume-assertion"
                    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
    <saml:Issuer>https://gateway.tld/gssp/tiqr/metadata</saml:Issuer>
    <saml:Subject>
        <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">oom60v-3art</saml:NameID>
    </saml:Subject>
    <samlp:Scoping ProxyCount="10">
        <samlp:RequesterID>https://ra.tld/vetting-procedure/gssf/tiqr/metadata</samlp:RequesterID>
    </samlp:Scoping>
      <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://suaas.example.com/assurance/loa2</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>');

        $this->mockSessionData('_sf2_attributes', []);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('isAllowedToUseSecondFactorOnlyFor')
            ->with('oom60v-3art')
            ->andReturn(true)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/gssp/tiqr/metadata')
            ->andReturn($serviceProvider);

        // Init request
        $this->gatewayLoginService->singleSignOn($httpRequest, $originalRequest);

        // Assert log
        $this->assertSame([
            'emergency' => [],
            'alert' => [],
            'critical' => [],
            'error' => [],
            'warning' => [],
            'notice' => [
                'SP "https://gateway.tld/gssp/tiqr/metadata" is allowed to use SecondFactorOnly mode for nameid "oom60v-3art"',
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
            'surfnet/gateway/requestforce_authn' => false,
            'surfnet/gateway/requestresponse_controller' => 'SurfnetStepupGatewaySecondFactorOnlyBundle:SecondFactorOnly:respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'second_factor_only.response_context',
            'surfnet/gateway/auth_mode/_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c' => 'sfo',
            'surfnet/gateway/requestname_id' => 'oom60v-3art',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
        ], $this->getSessionData('attributes'));
    }


    /**
     * @test
     */
    public function it_should_throw_a_exception_when_second_factor_is_not_allowed_when_starting_verification_on_sfo_login_flow()
    {
        $this->expectException(RequesterFailureException::class);
        // Create request
        $httpRequest = new Request();
        $originalRequest = ReceivedAuthnRequest::from('<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                    ID="_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c"
                    Version="2.0"
                    IssueInstant="2017-04-18T16:35:32Z"
                    Destination="https://tiqr.tld/saml/sso"
                    AssertionConsumerServiceURL="https://gateway.tld/gssp/tiqr/consume-assertion"
                    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
    <saml:Issuer>https://gateway.tld/gssp/tiqr/metadata</saml:Issuer>
    <saml:Subject>
        <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">oom60v-3art</saml:NameID>
    </saml:Subject>
    <samlp:Scoping ProxyCount="10">
        <samlp:RequesterID>https://ra.tld/vetting-procedure/gssf/tiqr/metadata</samlp:RequesterID>
    </samlp:Scoping>
      <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://suaas.example.com/assurance/loa2</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>');

        $this->mockSessionData('_sf2_attributes', []);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('isAllowedToUseSecondFactorOnlyFor')
            ->with('oom60v-3art')
            ->andReturn(false)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/gssp/tiqr/metadata')
            ->andReturn($serviceProvider);

        // Init request
        $this->gatewayLoginService->singleSignOn($httpRequest, $originalRequest);
    }


    /**
     * @test
     */
    public function it_should_throw_a_exception_when_the_requestd_loa_is_not_supported_when_starting_verification_on_sfo_login_flow()
    {
        $this->expectException(RequesterFailureException::class);
        // Create request
        $httpRequest = new Request();
        $originalRequest = ReceivedAuthnRequest::from('<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                    ID="_7179b234fc69f75724c83cab795fc87475d2f6d88e97e43368c3966e398c"
                    Version="2.0"
                    IssueInstant="2017-04-18T16:35:32Z"
                    Destination="https://tiqr.tld/saml/sso"
                    AssertionConsumerServiceURL="https://gateway.tld/gssp/tiqr/consume-assertion"
                    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
    <saml:Issuer>https://gateway.tld/gssp/tiqr/metadata</saml:Issuer>
    <saml:Subject>
        <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">oom60v-3art</saml:NameID>
    </saml:Subject>
    <samlp:Scoping ProxyCount="10">
        <samlp:RequesterID>https://ra.tld/vetting-procedure/gssf/tiqr/metadata</samlp:RequesterID>
    </samlp:Scoping>
      <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://suaas.example.com/assurance/not-supported-loa</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>');

        $this->mockSessionData('_sf2_attributes', []);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('isAllowedToUseSecondFactorOnlyFor')
            ->with('oom60v-3art')
            ->andReturn(true)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/gssp/tiqr/metadata')
            ->andReturn($serviceProvider);

        // Init request
        $this->gatewayLoginService->singleSignOn($httpRequest, $originalRequest);
    }

    /**
     * @param array $idpConfiguration
     * @param array $loaAliases
     * @param array $loaLevels
     * @param DateTime $now
     * @param array $sessionData
     */
    private function initGatewayLoginService(array $loaLevels,  array $loaAliases)
    {
        $session = new Session($this->sessionStorage);
        $this->stateHandler = new ProxyStateHandler($session, 'surfnet/gateway/request');
        $samlLogger = new SamlAuthenticationLogger($this->logger);

        $this->loaResolutionService = $this->mockLoaResolutionService($loaLevels);
        $this->postBinding = Mockery::mock(PostBinding::class);
        $this->redirectBinding = Mockery::mock(RedirectBinding::class);
        $this->samlEntityService = Mockery::mock(SamlEntityService::class);

        $loaAliasLookup = new LoaAliasLookupService($loaAliases);
        $loaResolutionService = new SecondFactorLoaResolutionService($this->logger, $loaAliasLookup, $this->loaResolutionService);
        $secondFactorOnlyNameValidatorService = new SecondFactorOnlyNameIdValidationService($this->logger, $this->samlEntityService);
        $httpBindingFactory = new HttpBindingFactory($this->redirectBinding, $this->postBinding);

        $this->gatewayLoginService = new LoginService(
            $this->logger,
            $samlLogger,
            $this->stateHandler,
            $httpBindingFactory,
            $secondFactorOnlyNameValidatorService,
            $loaResolutionService
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
