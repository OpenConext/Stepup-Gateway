<?php
/**
 * Copyright 2018 SURFnet bv
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
use Mockery\MockInterface;
use SAML2\DOMDocumentFactory;
use SAML2\Response;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\StepupGateway\GatewayBundle\Exception\ResponseFailureException;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Exception\UnknownInResponseToException;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidSubjectException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\SecondfactorVerificationRequiredException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\AllowedServiceProviders;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ConnectedServiceProviders;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\ProxyResponseFactory;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway\ConsumeAssertionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;

class ConsumeAssertionServiceTest extends GatewaySamlTestCase
{
    /** @var Mockery\Mock|ConsumeAssertionService */
    private $samlProxyConsumeAssertionService;

    /** @var StateHandler */
    private $stateHandler;

    /** @var ResponseContext */
    private $responseContext;

    /** @var Mockery\Mock|PostBinding */
    private $postBinding;

    /** @var Mockery\Mock|SamlEntityService */
    private $samlEntityService;

    /** @var IdentityProvider */
    private $remoteIdp;

    /** @var IdentityProvider */
    private $idp;

    /** @var Provider */
    private $provider;

    /** @var ProxyResponseFactory */
    private $proxyResponseFactory;

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


        $connectedServiceProviders = [
            'https://gateway.tld/authentication/metadata',
        ];

        // init gateway service
        $this->initSamlProxyService($idpConfiguration, $remoteIdpConfiguration, $spConfiguration, $connectedServiceProviders, $now);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_should_update_the_state_when_receiving_a_saml_response_when_consuming_assertions_on_gssp_registration_and_gssp_verification_flows(): void {

        $samlResponse = '<samlp:Response
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="CORTO1111111111222222222233333333334444444444"
    Version="2.0"
    IssueInstant="2014-10-22T11:07:08Z"
    Destination="https://gateway.org/acs"
    InResponseTo="_000000aabbccddeeffaabbccddeeffaabbccddeeff">
  <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xmlns:xs="http://www.w3.org/001/XMLSchema"
        ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee"
        Version="2.0"
        IssueInstant="2014-10-22T11:07:08Z">
    <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>UPnYtWy9KFV43qcT82Sd5iIUFH3f1Bv9NJSqaLF82Rw=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>QZr...==</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MII...=</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData
            NotOnOrAfter="2014-10-22T11:12:08Z"
            Recipient="https://gateway.org/acs"
            InResponseTo="_mocked_generated_id"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://gateway.org/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement
            AuthnInstant="2014-10-22T11:07:07Z"
            SessionNotOnOrAfter="2014-10-22T19:07:07Z"
            SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
        <saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority>
      </saml:AuthnContext>
    </saml:AuthnStatement>
    <saml:AttributeStatement>
      <saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue>
          <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID>
        </saml:AttributeValue>
      </saml:Attribute>
    </saml:AttributeStatement>
  </saml:Assertion>
</samlp:Response>';

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/gssp/test_provider/request_id' => '_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e',
            'surfnet/gateway/gssp/test_provider/service_provider' => 'https://gateway.tld/authentication/metadata',
            'surfnet/gateway/gssp/test_provider/assertion_consumer_service_url' => 'https://gateway.tld/authentication/consume-assertion',
            'surfnet/gateway/gssp/test_provider/relay_state' => '',
            'surfnet/gateway/gssp/test_provider/gateway_request_id' => '_mocked_generated_id',
        ]);

        $httpRequest = Request::create('idp.nl/sso-url');
        $httpRequest->request->set('SAMLResponse', $samlResponse);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://gateway.tld/authentication/consume-assertion', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/authentication/metadata')
            ->andReturn($serviceProvider);

        $this->mockPostBinding($samlResponse);

        // Handle assertion
        $response = $this->samlProxyConsumeAssertionService->consumeAssertion($this->provider, $httpRequest, $this->proxyResponseFactory);

        // Assertions
        // Assert proxy response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('<?xml version="1.0" encoding="UTF-8"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z" InResponseTo="_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e"><saml:Issuer>remote-idp.nl/entity-id</saml:Issuer><samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></samlp:Status><saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="_mocked_generated_id" Version="2.0" IssueInstant="2018-08-17T08:58:20Z"><saml:Issuer>remote-idp.nl/entity-id</saml:Issuer><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
    <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
  <ds:Reference URI="#_mocked_generated_id"><ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></ds:Transforms><ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/><ds:DigestValue>ZlLyxVmxqgur07WJpBNCze+bTY7vdYzDyUUPl+f5e1o=</ds:DigestValue></ds:Reference></ds:SignedInfo><ds:SignatureValue>jq4q1lqNa/SgMbDnUJbg4LcL9JLbfghYr0caWhuq1cAWCqvM5mtbVYC0GtaCdHsUwT98+n8JVEEqLAQObKqWMeE7wAhebVJ0zjxNkW93YdDraV+uEK8o+SuqlA3dBKPFJtzfVJCCGyz14XZye+20kTskbqDSJBLQUU8OakcPoJvZMBbBNHFj4Hzx3mfW5TmiKCg0OD9r6/DENfLmufWsdQKncQpYRpLMlL0EdCH6V6fYyD9Snxq/0oRmxj7lV2GmGK0te90pB7XWT+BXeKTb0ZfdRw5Z4Jha0iXhYgQwP6y88Kc5C+FbeCxXJdBq69c9Uu8M2r44bvEtwPRwfy9oRg==</ds:SignatureValue>
<ds:KeyInfo><ds:X509Data><ds:X509Certificate>MIIDnzCCAoegAwIBAgIJANcnhwDcAwrzMA0GCSqGSIb3DQEBCwUAMGUxCzAJBgNVBAYTAk5MMRAwDgYDVQQIDAdaZWVsYW5kMRMwEQYDVQQHDApWbGlzc2luZ2VuMRMwEQYDVQQKDApJYnVpbGRpbmdzMQswCQYDVQQLDAJJVDENMAsGA1UEAwwEdGVzdDAgFw0xODA4MjMwOTAwNTJaGA8yMTE4MDczMDA5MDA1MlowZTELMAkGA1UEBhMCTkwxEDAOBgNVBAgMB1plZWxhbmQxEzARBgNVBAcMClZsaXNzaW5nZW4xEzARBgNVBAoMCklidWlsZGluZ3MxCzAJBgNVBAsMAklUMQ0wCwYDVQQDDAR0ZXN0MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1UJZlxkh234YNaIRFWl6bnmDG1eoHOrAJmkRafXqMemV4Hex/1RJDNC8Wtn+OZ4qskaQipuuePEFX5tTXhWfuG5hoVyz0GO/n4BFzxi3Fup6rHgP1Fk8VdczVEU54w6UJmZZNfBkqlhpDmSnzZpH5llVRQsl3L0LIBLbW++wA9Xktf9IpjShXQQ/LPkODBrL6VA2Gip8gmR2L8eji81EAyMyS5heRwYtMZDATs7OsYF242UEzwgGsEHShF0196RG1LiGFk91veB2yN/N0pOB0Q6LEgMha5aYNVe0ITMR2LVR0OmaX56M7JyMg9v4ks63bWpA2ncerqcjFDyLcT27+wIDAQABo1AwTjAdBgNVHQ4EFgQUJpQDi7muaLSnkEx3saixUWbYipcwHwYDVR0jBBgwFoAUJpQDi7muaLSnkEx3saixUWbYipcwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQsFAAOCAQEAQAERp/KtBxGYBCYFxBQBYlu0mqph/IcNdjFJfdilrchYMn7ofTY7ANEUhxFz3T10xVchD9fPbEJ4WcRtm69N5sSUbOO677FOk2cAe5JEMzb+papFghSLr3FGfYbQxaqlKLj/cqJPS8DQh8oPb7aMfIdTYwTMDBpEPS6XsNiP7xWYlvYEsNtS3vkqp1A3T4LC0NPOJWvjZQtyjTWog50G3K3UhViy6kBnbHxLXK5NJkZ0OlnknahS5gYGxI0HW14AVg4EDIe8UQC5vQHuA3fV6qfKt0G2/5w/XR5YmKiRXB0c158HgqiUC0Chqz/j1J8dwgYrl6Yo/kQ0591izo1WIw==</ds:X509Certificate></ds:X509Data></ds:KeyInfo></ds:Signature><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID><saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><saml:SubjectConfirmationData NotOnOrAfter="2018-08-17T09:03:20Z" InResponseTo="_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e"/></saml:SubjectConfirmation></saml:Subject><saml:Conditions NotBefore="2018-08-17T08:58:20Z" NotOnOrAfter="2018-08-17T09:03:20Z"><saml:AudienceRestriction><saml:Audience>https://gateway.tld/authentication/metadata</saml:Audience></saml:AudienceRestriction></saml:Conditions><saml:AuthnStatement AuthnInstant="2014-10-22T11:07:07Z"><saml:AuthnContext><saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef><saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority></saml:AuthnContext></saml:AuthnStatement><saml:AttributeStatement><saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3"><saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue></saml:Attribute><saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10"><saml:AttributeValue><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID></saml:AttributeValue></saml:Attribute></saml:AttributeStatement></saml:Assertion></samlp:Response>
', $response->toUnsignedXML()->ownerDocument->saveXML());

        // Assert log
        $this->assertSame([
            'emergency' => [],
            'alert' => [],
            'critical' => [],
            'error' => [],
            'warning' => [],
            'notice' => [
                'Received SAMLResponse, attempting to process for Proxy Response',
                'Successfully processed SAMLResponse',
                'Responding to request "_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e" with response based on response from the remote IdP with response "_mocked_generated_id"',
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
        ], $this->getSessionData('attributes'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_should_throw_an_exception_when_the_post_binding_could_not_be_processed_when_receiving_a_saml_response_when_consuming_assertions_on_gssp_registration_and_gssp_verification_flows(): void
    {
        $this->expectException(ResponseFailureException::class);
        $samlResponse = '<samlp:Response
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="CORTO1111111111222222222233333333334444444444"
    Version="2.0"
    IssueInstant="2014-10-22T11:07:08Z"
    Destination="https://gateway.org/acs"
    InResponseTo="_000000aabbccddeeffaabbccddeeffaabbccddeeff">
  <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xmlns:xs="http://www.w3.org/001/XMLSchema"
        ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee"
        Version="2.0"
        IssueInstant="2014-10-22T11:07:08Z">
    <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>UPnYtWy9KFV43qcT82Sd5iIUFH3f1Bv9NJSqaLF82Rw=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>QZr...==</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MII...=</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData
            NotOnOrAfter="2014-10-22T11:12:08Z"
            Recipient="https://gateway.org/acs"
            InResponseTo="_mocked_generated_id"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://gateway.org/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement
            AuthnInstant="2014-10-22T11:07:07Z"
            SessionNotOnOrAfter="2014-10-22T19:07:07Z"
            SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
        <saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority>
      </saml:AuthnContext>
    </saml:AuthnStatement>
    <saml:AttributeStatement>
      <saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue>
          <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID>
        </saml:AttributeValue>
      </saml:Attribute>
    </saml:AttributeStatement>
  </saml:Assertion>
</samlp:Response>';

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/gssp/test_provider/request_id' => '_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e',
            'surfnet/gateway/gssp/test_provider/service_provider' => 'https://gateway.tld/authentication/metadata',
            'surfnet/gateway/gssp/test_provider/assertion_consumer_service_url' => 'https://gateway.tld/authentication/consume-assertion',
            'surfnet/gateway/gssp/test_provider/relay_state' => '',
            'surfnet/gateway/gssp/test_provider/gateway_request_id' => '_mocked_generated_id',
        ]);

        $httpRequest = Request::create('idp.nl/sso-url');
        $httpRequest->request->set('SAMLResponse', $samlResponse);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://gateway.tld/authentication/consume-assertion', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/authentication/metadata')
            ->andReturn($serviceProvider);

        $this->postBinding->shouldReceive('processResponse')
            ->andThrow(\Exception::class, 'Unable to process response');

        // Handle assertion
        $this->samlProxyConsumeAssertionService->consumeAssertion($this->provider, $httpRequest, $this->proxyResponseFactory);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_should_throw_an_exception_when_in_response_to_is_invalid_when_receiving_a_saml_response_when_consuming_assertions_on_gssp_registration_and_gssp_verification_flows(): void
    {
        $this->expectException(UnknownInResponseToException::class);
        $samlResponse = '<samlp:Response
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="CORTO1111111111222222222233333333334444444444"
    Version="2.0"
    IssueInstant="2014-10-22T11:07:08Z"
    Destination="https://gateway.org/acs"
    InResponseTo="_000000aabbccddeeffaabbccddeeffaabbccddeeff">
  <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xmlns:xs="http://www.w3.org/001/XMLSchema"
        ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee"
        Version="2.0"
        IssueInstant="2014-10-22T11:07:08Z">
    <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>UPnYtWy9KFV43qcT82Sd5iIUFH3f1Bv9NJSqaLF82Rw=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>QZr...==</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MII...=</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData
            NotOnOrAfter="2014-10-22T11:12:08Z"
            Recipient="https://gateway.org/acs"
            InResponseTo="_mocked_generated_id"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://gateway.org/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement
            AuthnInstant="2014-10-22T11:07:07Z"
            SessionNotOnOrAfter="2014-10-22T19:07:07Z"
            SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
        <saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority>
      </saml:AuthnContext>
    </saml:AuthnStatement>
    <saml:AttributeStatement>
      <saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue>
          <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID>
        </saml:AttributeValue>
      </saml:Attribute>
    </saml:AttributeStatement>
  </saml:Assertion>
</samlp:Response>';

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/gssp/test_provider/request_id' => '_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e',
            'surfnet/gateway/gssp/test_provider/service_provider' => 'https://gateway.tld/authentication/metadata',
            'surfnet/gateway/gssp/test_provider/assertion_consumer_service_url' => 'https://gateway.tld/authentication/consume-assertion',
            'surfnet/gateway/gssp/test_provider/relay_state' => '',
            'surfnet/gateway/gssp/test_provider/gateway_request_id' => '_mocked_invalid_generated_id',
        ]);

        $httpRequest = Request::create('idp.nl/sso-url');
        $httpRequest->request->set('SAMLResponse', $samlResponse);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://gateway.tld/authentication/consume-assertion', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/authentication/metadata')
            ->andReturn($serviceProvider);

        $this->mockPostBinding($samlResponse);

        // Handle assertion
        $this->samlProxyConsumeAssertionService->consumeAssertion($this->provider, $httpRequest, $this->proxyResponseFactory);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_should_throw_an_exception_when_to_subject_is_invalid_when_receiving_a_saml_response_when_consuming_assertions_on_gssp_registration_and_gssp_verification_flows(): void
    {
        $this->expectException(InvalidSubjectException::class);
        $samlResponse = '<samlp:Response
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="CORTO1111111111222222222233333333334444444444"
    Version="2.0"
    IssueInstant="2014-10-22T11:07:08Z"
    Destination="https://gateway.org/acs"
    InResponseTo="_000000aabbccddeeffaabbccddeeffaabbccddeeff">
  <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xmlns:xs="http://www.w3.org/001/XMLSchema"
        ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee"
        Version="2.0"
        IssueInstant="2014-10-22T11:07:08Z">
    <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>UPnYtWy9KFV43qcT82Sd5iIUFH3f1Bv9NJSqaLF82Rw=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>QZr...==</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MII...=</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData
            NotOnOrAfter="2014-10-22T11:12:08Z"
            Recipient="https://gateway.org/acs"
            InResponseTo="_mocked_generated_id"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://gateway.org/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement
            AuthnInstant="2014-10-22T11:07:07Z"
            SessionNotOnOrAfter="2014-10-22T19:07:07Z"
            SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
        <saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority>
      </saml:AuthnContext>
    </saml:AuthnStatement>
    <saml:AttributeStatement>
      <saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue>
          <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID>
        </saml:AttributeValue>
      </saml:Attribute>
    </saml:AttributeStatement>
  </saml:Assertion>
</samlp:Response>';

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/gssp/test_provider/request_id' => '_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e',
            'surfnet/gateway/gssp/test_provider/service_provider' => 'https://gateway.tld/authentication/metadata',
            'surfnet/gateway/gssp/test_provider/assertion_consumer_service_url' => 'https://gateway.tld/authentication/consume-assertion',
            'surfnet/gateway/gssp/test_provider/relay_state' => '',
            'surfnet/gateway/gssp/test_provider/gateway_request_id' => '_mocked_generated_id',
            'surfnet/gateway/gssp/test_provider/subject' => 'invalid-subject'
        ]);

        $httpRequest = Request::create('idp.nl/sso-url');
        $httpRequest->request->set('SAMLResponse', $samlResponse);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://gateway.tld/authentication/consume-assertion', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/authentication/metadata')
            ->andReturn($serviceProvider);

        $this->mockPostBinding($samlResponse);

        // Handle assertion
        $this->samlProxyConsumeAssertionService->consumeAssertion($this->provider, $httpRequest, $this->proxyResponseFactory);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_should_throw_an_verification_exception_when_receiving_a_saml_response_when_consuming_assertions_on_gssp_registration_and_gssp_verification_flows(): void
    {
        $this->expectException(SecondfactorVerificationRequiredException::class);
        $samlResponse = '<samlp:Response
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="CORTO1111111111222222222233333333334444444444"
    Version="2.0"
    IssueInstant="2014-10-22T11:07:08Z"
    Destination="https://gateway.org/acs"
    InResponseTo="_000000aabbccddeeffaabbccddeeffaabbccddeeff">
  <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xmlns:xs="http://www.w3.org/001/XMLSchema"
        ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee"
        Version="2.0"
        IssueInstant="2014-10-22T11:07:08Z">
    <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>UPnYtWy9KFV43qcT82Sd5iIUFH3f1Bv9NJSqaLF82Rw=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>QZr...==</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MII...=</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData
            NotOnOrAfter="2014-10-22T11:12:08Z"
            Recipient="https://gateway.org/acs"
            InResponseTo="_mocked_generated_id"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://gateway.org/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement
            AuthnInstant="2014-10-22T11:07:07Z"
            SessionNotOnOrAfter="2014-10-22T19:07:07Z"
            SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
        <saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority>
      </saml:AuthnContext>
    </saml:AuthnStatement>
    <saml:AttributeStatement>
      <saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue>
          <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID>
        </saml:AttributeValue>
      </saml:Attribute>
    </saml:AttributeStatement>
  </saml:Assertion>
</samlp:Response>';

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/gssp/test_provider/request_id' => '_1b8f282a9c194b264ef68761171539380de78b45038f65b8609df868f55e',
            'surfnet/gateway/gssp/test_provider/service_provider' => 'https://gateway.tld/authentication/metadata',
            'surfnet/gateway/gssp/test_provider/assertion_consumer_service_url' => 'https://gateway.tld/authentication/consume-assertion',
            'surfnet/gateway/gssp/test_provider/relay_state' => '',
            'surfnet/gateway/gssp/test_provider/gateway_request_id' => '_mocked_generated_id',
            'surfnet/gateway/gssp/test_provider/is_second_factor_verification' => true,
        ]);

        $httpRequest = Request::create('idp.nl/sso-url');
        $httpRequest->request->set('SAMLResponse', $samlResponse);

        // Mock service provider
        $serviceProvider = Mockery::mock(ServiceProvider::class)
            ->shouldReceive('determineAcsLocation')
            ->with('https://gateway.tld/authentication/consume-assertion', $this->logger)
            ->getMock();

        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('https://gateway.tld/authentication/metadata')
            ->andReturn($serviceProvider);

        $this->mockPostBinding($samlResponse);

        // Handle assertion
        $this->samlProxyConsumeAssertionService->consumeAssertion($this->provider, $httpRequest, $this->proxyResponseFactory);
    }

    private function initSamlProxyService(array $remoteIdpConfiguration, array $idpConfiguration, array $spConfiguration, array $connectedServiceProviders, DateTime $now): void
    {
        $session = new Session($this->sessionStorage);
        $requestStack = Mockery::mock(RequestStack::class);
        $requestStack->shouldReceive('getSession')->andReturn($session);
        $this->stateHandler = new StateHandler($requestStack, 'test_provider');

        $samlLogger = new SamlAuthenticationLogger($this->logger);

        $this->remoteIdp = new IdentityProvider($remoteIdpConfiguration);
        $this->idp = new IdentityProvider($idpConfiguration);
        $serviceProvider = new ServiceProvider($spConfiguration);
        $this->postBinding = Mockery::mock(PostBinding::class);
        $this->samlEntityService = Mockery::mock(SamlEntityService::class);
        $allowed = new AllowedServiceProviders($connectedServiceProviders, 'regex');
        $connectedServiceProviders = new ConnectedServiceProviders($this->samlEntityService, $allowed);

        $this->provider = new Provider(
            'testProvider',
            $this->idp,
            $serviceProvider,
            $this->remoteIdp,
            $this->stateHandler
        );

        $assertionSigningService = new AssertionSigningService($this->idp);
        $this->proxyResponseFactory = new ProxyResponseFactory($this->logger, $this->idp, $this->provider->getStateHandler(), $assertionSigningService, $now);

        $this->responseContext = new ResponseContext(
            $this->remoteIdp,
            $this->samlEntityService,
            $this->stateHandler,
            $this->logger,
            $now
        );

        $this->samlProxyConsumeAssertionService = new ConsumeAssertionService(
            $this->logger,
            $samlLogger,
            $this->postBinding,
            $connectedServiceProviders
        );
    }

    /**
     * @param string $samlResponseXml
     */
    private function mockPostBinding($samlResponseXml): void
    {
        $asXml    = DOMDocumentFactory::fromString($samlResponseXml);

        $response = new Response($asXml->documentElement);
        $assertion = $response->getAssertions()[0];

        $this->postBinding->shouldReceive('processResponse')
            ->andReturn($assertion);
    }
}
