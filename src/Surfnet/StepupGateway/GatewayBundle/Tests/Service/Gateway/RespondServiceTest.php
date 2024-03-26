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
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDefinition;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDictionary;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\RespondService;
use Surfnet\StepupGateway\GatewayBundle\Service\ProxyResponseService;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

final class RespondServiceTest extends GatewaySamlTestCase
{
    /** @var Mockery\Mock|RespondService */
    private ?\Surfnet\StepupGateway\GatewayBundle\Service\Gateway\RespondService $gatewayRespondService = null;

    /** @var Mockery\Mock|ProxyStateHandler */
    private ?\Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler $stateHandler = null;

    private ?\Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext $responseContext = null;

    /** @var Mockery\Mock|LoaResolutionService */
    private ?\Surfnet\StepupBundle\Service\LoaResolutionService $loaResolutionService = null;

    /** @var Mockery\Mock|PostBinding */
    private $postBinding;

    /** @var Mockery\Mock|PostBinding */
    private $redirectBinding;

    /** @var Mockery\Mock|SamlEntityService */
    private $samlEntityService;

    private ?\Surfnet\SamlBundle\Entity\IdentityProvider $remoteIdp = null;

    private ?\Surfnet\SamlBundle\SAML2\Attribute\AttributeDictionary $attributeDictionary = null;

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

        $dictionaryAttributes = [
            [
                'mail',
                'urn:mace:dir:attribute-def:mail',
                'urn:oid:0.9.2342.19200300.100.1.3',
            ],
            [
                'eduPersonTargetedID',
                'urn:mace:dir:attribute-def:eduPersonTargetedID',
                'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
            ],
            [
                'internalCollabPersonId',
                'urn:mace:surf.nl:attribute-def:internal-collabPersonId',
                null,
            ],
        ];

        $loaLevels = [
            [1, 'http://stepup.example.com/assurance/loa1'],
            [2, 'http://stepup.example.com/assurance/loa2'],
            [3, 'http://stepup.example.com/assurance/loa3'],
        ];

        // init gateway service
        $this->initGatewayService($idpConfiguration, $dictionaryAttributes, $loaLevels, $now);
    }

    /**
     * @test
     */
    public function it_should_return_a_valid_saml_response_and_update_state_when_the_verification_is_succeeded_on_login_flow(): void
    {

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://sp.com/acs', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://sp.com/metadata')
            ->andReturn($serviceProvider);

        // Mock second factor
        $secondFactor = Mockery::mock(SecondFactor::class);
        $secondFactor->secondFactorId = 'mocked-second-factor-id';
        $secondFactor->displayLocale = 'nl_NL';
        $secondFactor->shouldReceive('getLoaLevel')
            ->andReturn(2);

        // Mock second factor service
        $this->secondFactorService->shouldReceive('findByUuid')
            ->with('mocked-second-factor-id')
            ->andReturn($secondFactor);

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
            'surfnet/gateway/requestrelay_state' => 'relay_state',
            'surfnet/gateway/requestresponse_controller' => 'SurfnetStepupGatewayGatewayBundle:Gateway:respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'gateway.proxy.response_context',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestgateway_request_id' => '_mocked_generated_id',
            'surfnet/gateway/requestname_id' => '724cca6778a1d3db16b65c40d4c378d011f220be',
            'surfnet/gateway/requestauthenticating_idp' => 'https://proxied-idp.edu/',
            'surfnet/gateway/requestresponse_assertion' => '<?xml version="1.0"?>
<saml:Assertion xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee" Version="2.0" IssueInstant="2014-10-22T11:09:59Z"><saml:Issuer>https://idp.edu/metadata</saml:Issuer><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID><saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><saml:SubjectConfirmationData Recipient="https://gateway.org/acs" InResponseTo="_mocked_generated_id"/></saml:SubjectConfirmation></saml:Subject><saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z"><saml:AudienceRestriction><saml:Audience>https://gateway.org/metadata</saml:Audience></saml:AudienceRestriction></saml:Conditions><saml:AuthnStatement AuthnInstant="2014-10-22T11:07:07Z" SessionNotOnOrAfter="2014-10-22T19:07:07Z" SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b"><saml:AuthnContext><saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef><saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority></saml:AuthnContext></saml:AuthnStatement><saml:AttributeStatement><saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue></saml:Attribute><saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID></saml:AttributeValue></saml:Attribute></saml:AttributeStatement></saml:Assertion>
',
        ]);

        // This should be done in the SecondFactorController on success
        $this->responseContext->saveSelectedSecondFactor($secondFactor);
        $this->responseContext->markSecondFactorVerified();

        // Handle respond
        $response = $this->gatewayRespondService->respond($this->responseContext);

        // Assert response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('<?xml version="1.0" encoding="UTF-8"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z" InResponseTo="_123456789012345678901234567890123456789012"><saml:Issuer>idp.nl/entity-id</saml:Issuer><samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></samlp:Status><saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z"><saml:Issuer>idp.nl/entity-id</saml:Issuer><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
    <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
  <ds:Reference URI="#_mocked_generated_id"><ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></ds:Transforms><ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/><ds:DigestValue>BXdfrSLvhgwxdHAtZ4itcoSDEdi2gh3elXZbNEnghE4=</ds:DigestValue></ds:Reference></ds:SignedInfo><ds:SignatureValue>m1QOk7X7a/9XGwd6tgI9N2zmAcPguXWz6tnUDK4V/ZGUAQSzZWMjYyfY4YVpkOZ8zDN+M2IQCz9FqOBxf+iu7FAYMiKWqS/dKlGRY4oMvAU3ZPIk0lAopr/eBc07pomyotnKOFGFapnkmY1QxcqiPjcX7zpIT+bhtByWcoq1LUMEJNa7A8Xvx3cgYQgRaYapLD9ou5kuLPo4BAU5nJiraDtvxyMqQCnQ1NZySnHYTknCUcQDjzNkP5MCQh9vM3O88egP/GE3QtaRd31wX4XmQBCyY6LaHnM8XQLAkibprY6VT04CjEjQCdEBL5OTcERnMaa7hAEpkQlvQIu5N0xBCg==</ds:SignatureValue>
<ds:KeyInfo><ds:X509Data><ds:X509Certificate>MIID5zCCAs+gAwIBAgIJAL8E2GQ671hSMA0GCSqGSIb3DQEBCwUAMIGIMQswCQYDVQQGEwJOTDEQMA4GA1UECAwHWmVlbGFuZDETMBEGA1UEBwwKVmxpc3NpbmdlbjETMBEGA1UECgwKSWJ1aWxkaW5nczELMAkGA1UECwwCSVQxDzANBgNVBAMMBmlkcC5ubDEfMB0GCSqGSIb3DQEJARYQdGVzdEBleGFtcGxlLmNvbTAgFw0xODA4MjAwOTM2NDVaGA8yMTE4MDcyNzA5MzY0NVowgYgxCzAJBgNVBAYTAk5MMRAwDgYDVQQIDAdaZWVsYW5kMRMwEQYDVQQHDApWbGlzc2luZ2VuMRMwEQYDVQQKDApJYnVpbGRpbmdzMQswCQYDVQQLDAJJVDEPMA0GA1UEAwwGaWRwLm5sMR8wHQYJKoZIhvcNAQkBFhB0ZXN0QGV4YW1wbGUuY29tMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvmsJW2d+48UMs/r7BLQZWn4v7bXAQZxlAy5OE+SXZyafgiI22pJF1qRE6MiqYoVsJq4F7qfCv/8pFjUmnVIomaeRT88MI4nGrlEVL12SLzBzM/ftSrTP0FhoM8dmAW9VJUghjp7UYm7SFuPok0HpOV9A/5Z6nrkZ/mnEo24CDcFr5V06rg3fPofYD6FN1aIaYoNu3gtUo9rnS1fDw4m1fj5+X1VGKTqmvKpHTBS5cWZjvlU0Fw0N4tiJmJSq3sCclPvVXBXKcJeBhKA/jEurVTsqWerNfZ8O8iolwuXQLyva0ugvSRU8G0zIJMINUIAi03ulI978D1Pq0ZYIbcKxKwIDAQABo1AwTjAdBgNVHQ4EFgQUN6TQ5gwRg6ZFrjl8YuVssW59+RkwHwYDVR0jBBgwFoAUN6TQ5gwRg6ZFrjl8YuVssW59+RkwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQsFAAOCAQEARPxXmwswRaUg0jh6v9Q6IwOhPrPxw3uY1KxSX/+cfjPKVJXyfQQshJne4rAfLbBZPEmAbXi8xmMQvk7SjFsq8EjjGKCw9D5YikeucstxC6Ri4pRQcZcTi/o7Q06eKi2LFC7UM0RXIKBtCSUI5wYRzExFW0sUcTnfeCNdf0lk4fRVMrvccF04F7QANDcQSeMbdSSZZrUrEGYR+hGLypsq/7p5eSxgs8ooJhBgLULzOhfYz6qnCi5AHxvjKxogyvaDIdUvJUY/eU5xWpQT2IEW594tF876NNhnjPmZSZrGzRwkH2T0F9RZEf9sEjtd2tbbETjAzsBNNMOsGdLr2vO3WQ==</ds:X509Certificate></ds:X509Data></ds:KeyInfo></ds:Signature><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID><saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><saml:SubjectConfirmationData NotOnOrAfter="2018-08-17T09:03:20Z" InResponseTo="_123456789012345678901234567890123456789012"/></saml:SubjectConfirmation></saml:Subject><saml:Conditions NotBefore="2018-08-17T08:58:20Z" NotOnOrAfter="2018-08-17T09:03:20Z"><saml:AudienceRestriction><saml:Audience>https://sp.com/metadata</saml:Audience></saml:AudienceRestriction></saml:Conditions><saml:AuthnStatement AuthnInstant="2014-10-22T11:07:07Z"><saml:AuthnContext><saml:AuthnContextClassRef>http://stepup.example.com/assurance/loa2</saml:AuthnContextClassRef><saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority><saml:AuthenticatingAuthority>https://idp.edu/metadata</saml:AuthenticatingAuthority></saml:AuthnContext></saml:AuthnStatement><saml:AttributeStatement><saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3"><saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue></saml:Attribute><saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10"><saml:AttributeValue><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID></saml:AttributeValue></saml:Attribute></saml:AttributeStatement></saml:Assertion></samlp:Response>
', $response->toUnsignedXML()->ownerDocument->saveXML());

        // Assert log
        $this->assertSame([
            'emergency' => [],
            'alert' => [],
            'critical' => [],
            'error' => [],
            'warning' => [],
            'notice' => [
                'Creating Response',
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
            'surfnet/gateway/requestrelay_state' => 'relay_state',
            'surfnet/gateway/requestresponse_controller' => 'SurfnetStepupGatewayGatewayBundle:Gateway:respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'gateway.proxy.response_context',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestgateway_request_id' => '_mocked_generated_id',
            'surfnet/gateway/requestname_id' => '724cca6778a1d3db16b65c40d4c378d011f220be',
            'surfnet/gateway/requestauthenticating_idp' => 'https://proxied-idp.edu/',
            'surfnet/gateway/requestresponse_assertion' => '<?xml version="1.0"?>
<saml:Assertion xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee" Version="2.0" IssueInstant="2014-10-22T11:09:59Z"><saml:Issuer>https://idp.edu/metadata</saml:Issuer><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID><saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><saml:SubjectConfirmationData Recipient="https://gateway.org/acs" InResponseTo="_mocked_generated_id"/></saml:SubjectConfirmation></saml:Subject><saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z"><saml:AudienceRestriction><saml:Audience>https://gateway.org/metadata</saml:Audience></saml:AudienceRestriction></saml:Conditions><saml:AuthnStatement AuthnInstant="2014-10-22T11:07:07Z" SessionNotOnOrAfter="2014-10-22T19:07:07Z" SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b"><saml:AuthnContext><saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef><saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority></saml:AuthnContext></saml:AuthnStatement><saml:AttributeStatement><saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue></saml:Attribute><saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID></saml:AttributeValue></saml:Attribute></saml:AttributeStatement></saml:Assertion>
',
            'surfnet/gateway/requestselected_second_factor' => 'mocked-second-factor-id',
            'surfnet/gateway/requestselected_second_factor_verified' => true,
            'surfnet/gateway/requestlocale' => 'nl_NL',
        ], $this->getSessionData('attributes'));


        /** reset state */
        $this->gatewayRespondService->resetRespondState($this->responseContext);

        // Assert session
        $this->assertSame([
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
            'surfnet/gateway/requestrelay_state' => 'relay_state',
            'surfnet/gateway/requestresponse_controller' => 'SurfnetStepupGatewayGatewayBundle:Gateway:respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'gateway.proxy.response_context',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestgateway_request_id' => '_mocked_generated_id',
            'surfnet/gateway/requestname_id' => '724cca6778a1d3db16b65c40d4c378d011f220be',
            'surfnet/gateway/requestauthenticating_idp' => 'https://proxied-idp.edu/',
            'surfnet/gateway/requestresponse_assertion' => '<?xml version="1.0"?>
<saml:Assertion xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee" Version="2.0" IssueInstant="2014-10-22T11:09:59Z"><saml:Issuer>https://idp.edu/metadata</saml:Issuer><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID><saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><saml:SubjectConfirmationData Recipient="https://gateway.org/acs" InResponseTo="_mocked_generated_id"/></saml:SubjectConfirmation></saml:Subject><saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z"><saml:AudienceRestriction><saml:Audience>https://gateway.org/metadata</saml:Audience></saml:AudienceRestriction></saml:Conditions><saml:AuthnStatement AuthnInstant="2014-10-22T11:07:07Z" SessionNotOnOrAfter="2014-10-22T19:07:07Z" SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b"><saml:AuthnContext><saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef><saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority></saml:AuthnContext></saml:AuthnStatement><saml:AttributeStatement><saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue></saml:Attribute><saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID></saml:AttributeValue></saml:Attribute></saml:AttributeStatement></saml:Assertion>
',
            // the second factor id is reset later, we need it to determine we should set the sso 2fa cookie
            'surfnet/gateway/requestselected_second_factor' => 'mocked-second-factor-id',
            'surfnet/gateway/requestselected_second_factor_verified' => false,
            'surfnet/gateway/requestlocale' => 'nl_NL',
            'surfnet/gateway/requestsso_on_2fa_cookie_fingerprint' => ''
        ], $this->getSessionData('attributes'));
    }

    /**
     * @param int $now
     * @param array $sessionData
     */
    private function initGatewayService(array $idpConfiguration, array $dictionaryAttributes, array $loaLevels, DateTime $now): void
    {
        $session = new Session($this->sessionStorage);
        $requestStackMock = $this->createMock(RequestStack::class);
        $requestStackMock->method('getSession')->willReturn($session);

        $this->stateHandler = new ProxyStateHandler($requestStackMock, 'surfnet/gateway/request');
        $samlLogger = new SamlAuthenticationLogger($this->logger);

        $this->remoteIdp = new IdentityProvider($idpConfiguration);
        $this->loaResolutionService = $this->mockLoaResolutionService($loaLevels);
        $this->postBinding = Mockery::mock(PostBinding::class);
        $this->redirectBinding = Mockery::mock(RedirectBinding::class);
        $this->samlEntityService = Mockery::mock(SamlEntityService::class);
        $this->secondFactorService = Mockery::mock(SecondFactorService::class);
        $secondFactorTypeService = Mockery::mock(SecondFactorTypeService::class);

        $this->responseContext = new ResponseContext(
            $this->remoteIdp,
            $this->samlEntityService,
            $this->stateHandler,
            $this->logger,
            $now
        );

        $responseProxy = $this->mockResponseProxy($this->stateHandler, $this->remoteIdp, $dictionaryAttributes, $now);

        $this->gatewayRespondService = new RespondService(
            $samlLogger,
            $this->loaResolutionService,
            $responseProxy,
            $this->secondFactorService,
            $secondFactorTypeService
        );
    }

    /**
     * @param int $now
     * @return ProxyResponseService
     */
    private function mockResponseProxy(ProxyStateHandler $proxyStateHandler, IdentityProvider $remoteIdp, array $attributes, DateTime $now): \Surfnet\StepupGateway\GatewayBundle\Service\ProxyResponseService
    {
        $assertionSigningService = new AssertionSigningService($remoteIdp);

        $this->attributeDictionary = new AttributeDictionary();

        foreach ($attributes as $attribute) {
            $attributeDefinition = new AttributeDefinition($attribute[0], $attribute[1], $attribute[2]);
            $this->attributeDictionary->addAttributeDefinition($attributeDefinition);
        }

        $eptiAttribute = Mockery::mock(AttributeDefinition::class);
        $intrinsicLoa = Mockery::mock(Loa::class)
            ->shouldReceive('__toString')
            ->andReturn('http://stepup.example.com/assurance/loa2')
            ->getMock();

        return new ProxyResponseService($remoteIdp, $proxyStateHandler, $assertionSigningService, $this->attributeDictionary, $eptiAttribute, $intrinsicLoa, $now);
    }

    /**
     * @return LoaResolutionService
     */
    private function mockLoaResolutionService(array $loaLevels): \Surfnet\StepupBundle\Service\LoaResolutionService
    {
        $loaLevelObjects = [];
        foreach ($loaLevels as $level) {
            $loaLevelObjects[] = new Loa($level[0], $level[1]);
        }
        return new LoaResolutionService($loaLevelObjects);
    }
}
