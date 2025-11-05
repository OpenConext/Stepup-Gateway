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
use SAML2\DOMDocumentFactory;
use SAML2\Response;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\StepupGateway\GatewayBundle\Exception\ResponseFailureException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Exception\UnknownInResponseToException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\Gateway\ConsumeAssertionService;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

final class ConsumeAssertionServiceTest extends GatewaySamlTestCase
{
    /** @var Mockery\Mock|ConsumeAssertionService */
    private $gatewayConsumeAssertionService;

    /** @var Mockery\Mock|ProxyStateHandler */
    private $stateHandler;

    /** @var ResponseContext */
    private $responseContext;

    /** @var Mockery\Mock|PostBinding */
    private $postBinding;

    /** @var Mockery\Mock|SamlEntityService */
    private $samlEntityService;

    /** @var IdentityProvider */
    private $remoteIdp;

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

        // init gateway service
        $this->initGatewayService($idpConfiguration, $spConfiguration, $now);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_should_update_the_state_when_receiving_a_saml_response_when_consuming_assertions_on_login_flow(): void
    {
        $samlResponseXml = '<samlp:Response
        xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
        xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
        ID="CORTO1111111111222222222233333333334444444444"
        Version="2.0"
        IssueInstant="2014-10-22T11:09:59Z"
        Destination="https://gateway.org/acs"
        InResponseTo="_mocked_generated_id">
    <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
    <samlp:Status>
        <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
    </samlp:Status>
    <saml:Assertion
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:xs="http://www.w3.org/001/XMLSchema"
            ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee"
            Version="2.0"
            IssueInstant="2014-10-22T11:09:59Z">
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
                    <ds:DigestValue>7PL9R/HcO4CIa0bLKiojFMf63TcwvdTEOultiLpzY88=</ds:DigestValue>
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
            <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">
                724cca6778a1d3db16b65c40d4c378d011f220be
            </saml:NameID>
            <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
                <saml:SubjectConfirmationData
                        IssueInstant="2014-10-22T11:09:59Z"
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
            <saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3"
                            NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
                <saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue>
            </saml:Attribute>
            <saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10"
                            NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
                <saml:AttributeValue>
                    <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">
                        urn:collab:person:example.edu:jdoe
                    </saml:NameID>
                </saml:AttributeValue>
            </saml:Attribute>
        </saml:AttributeStatement>
    </saml:Assertion>
</samlp:Response>
';

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
            'surfnet/gateway/requestrelay_state' => 'relay_state',
            'surfnet/gateway/requestresponse_controller' => 'Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController::respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'gateway.proxy.response_context',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestgateway_request_id' => '_mocked_generated_id',
        ]);

        $this->mockPostBinding($samlResponseXml);

        $samlResponse = base64_encode($samlResponseXml);

        $httpRequest = Request::create('idp.nl/sso-url');
        $httpRequest->request->set('SAMLResponse', $samlResponse);

        // Consume assertion
        $this->gatewayConsumeAssertionService->consumeAssertion($httpRequest, $this->responseContext);

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
                'Forwarding to second factor controller for loa determination and handling',
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
            'surfnet/gateway/requestresponse_controller' => 'Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController::respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'gateway.proxy.response_context',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestgateway_request_id' => '_mocked_generated_id',
            'surfnet/gateway/requestname_id' => '724cca6778a1d3db16b65c40d4c378d011f220be',
            'surfnet/gateway/requestauthenticating_idp' => 'https://proxied-idp.edu/',
            'surfnet/gateway/requestresponse_assertion' => '<?xml version="1.0" encoding="UTF-8"?>
<saml:Assertion xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee" Version="2.0" IssueInstant="2014-10-22T11:09:59Z"><saml:Issuer>https://idp.edu/metadata</saml:Issuer><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID><saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><saml:SubjectConfirmationData Recipient="https://gateway.org/acs" InResponseTo="_mocked_generated_id"/></saml:SubjectConfirmation></saml:Subject><saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z"><saml:AudienceRestriction><saml:Audience>https://gateway.org/metadata</saml:Audience></saml:AudienceRestriction></saml:Conditions><saml:AuthnStatement AuthnInstant="2014-10-22T11:07:07Z" SessionNotOnOrAfter="2014-10-22T19:07:07Z" SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b"><saml:AuthnContext><saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef><saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority></saml:AuthnContext></saml:AuthnStatement><saml:AttributeStatement><saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue></saml:Attribute><saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID></saml:AttributeValue></saml:Attribute></saml:AttributeStatement></saml:Assertion>
',
        ], $this->getSessionData('attributes'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_should_throw_an_exception_when_the_post_binding_could_not_be_processed_when_receiving_a_saml_response_when_consuming_assertions_on_login_flow(): void
    {
        $this->expectException(ResponseFailureException::class);
        $samlResponseXml = '<samlp:Response
        xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
        xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
        ID="CORTO1111111111222222222233333333334444444444"
        Version="2.0"
        IssueInstant="2014-10-22T11:09:59Z"
        Destination="https://gateway.org/acs"
        InResponseTo="_mocked_generated_id">
    <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
    <samlp:Status>
        <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
    </samlp:Status>
    <saml:Assertion
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:xs="http://www.w3.org/001/XMLSchema"
            ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee"
            Version="2.0"
            IssueInstant="2014-10-22T11:09:59Z">
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
                    <ds:DigestValue>7PL9R/HcO4CIa0bLKiojFMf63TcwvdTEOultiLpzY88=</ds:DigestValue>
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
            <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">
                724cca6778a1d3db16b65c40d4c378d011f220be
            </saml:NameID>
            <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
                <saml:SubjectConfirmationData
                        IssueInstant="2014-10-22T11:09:59Z"
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
            <saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3"
                            NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
                <saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue>
            </saml:Attribute>
            <saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10"
                            NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
                <saml:AttributeValue>
                    <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">
                        urn:collab:person:example.edu:jdoe
                    </saml:NameID>
                </saml:AttributeValue>
            </saml:Attribute>
        </saml:AttributeStatement>
    </saml:Assertion>
</samlp:Response>
';

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
            'surfnet/gateway/requestrelay_state' => 'relay_state',
            'surfnet/gateway/requestresponse_controller' => 'Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController::respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'gateway.proxy.response_context',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestgateway_request_id' => '_mocked_generated_id',
        ]);

        $this->postBinding->shouldReceive('processResponse')
            ->andThrow(\Exception::class, 'Unable to process response');

        $samlResponse = base64_encode($samlResponseXml);

        $httpRequest = Request::create('idp.nl/sso-url');
        $httpRequest->request->set('SAMLResponse', $samlResponse);

        // Consume assertion
        $this->gatewayConsumeAssertionService->consumeAssertion($httpRequest, $this->responseContext);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_should_throw_an_exception_when_the_in_respone_to_is_invalid_when_receiving_a_saml_response_when_consuming_assertions_on_login_flow(): void
    {
        $this->expectException(UnknownInResponseToException::class);
        $samlResponseXml = '<samlp:Response
        xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
        xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
        ID="CORTO1111111111222222222233333333334444444444"
        Version="2.0"
        IssueInstant="2014-10-22T11:09:59Z"
        Destination="https://gateway.org/acs"
        InResponseTo="_mocked_generated_id">
    <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
    <samlp:Status>
        <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
    </samlp:Status>
    <saml:Assertion
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:xs="http://www.w3.org/001/XMLSchema"
            ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee"
            Version="2.0"
            IssueInstant="2014-10-22T11:09:59Z">
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
                    <ds:DigestValue>7PL9R/HcO4CIa0bLKiojFMf63TcwvdTEOultiLpzY88=</ds:DigestValue>
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
            <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">
                724cca6778a1d3db16b65c40d4c378d011f220be
            </saml:NameID>
            <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
                <saml:SubjectConfirmationData
                        IssueInstant="2014-10-22T11:09:59Z"
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
            <saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3"
                            NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
                <saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue>
            </saml:Attribute>
            <saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10"
                            NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
                <saml:AttributeValue>
                    <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">
                        urn:collab:person:example.edu:jdoe
                    </saml:NameID>
                </saml:AttributeValue>
            </saml:Attribute>
        </saml:AttributeStatement>
    </saml:Assertion>
</samlp:Response>
';

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
            'surfnet/gateway/requestrelay_state' => 'relay_state',
            'surfnet/gateway/requestresponse_controller' => 'Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController::respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'gateway.proxy.response_context',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestgateway_request_id' => '_mocked_invalid_generated_id',
        ]);

        $this->mockPostBinding($samlResponseXml);

        $samlResponse = base64_encode($samlResponseXml);

        $httpRequest = Request::create('idp.nl/sso-url');
        $httpRequest->request->set('SAMLResponse', $samlResponse);

        // Consume assertion
        $this->gatewayConsumeAssertionService->consumeAssertion($httpRequest, $this->responseContext);
    }

    public function test_it_stores_correct_collab_person_id_in_state(): void
    {
        $samlResponseXml = '<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="CORTO1111111111222222222233333333334444444444" Version="2.0" IssueInstant="2014-10-22T11:09:59Z" Destination="https://gateway.org/acs" InResponseTo="_mocked_generated_id">
    <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
    <samlp:Status>
        <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
    </samlp:Status>
    <saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/001/XMLSchema" ID="pfxaa30421c-e921-072d-d75a-decd1a4ee918" Version="2.0" IssueInstant="2014-10-22T11:09:59Z">
        <saml:Issuer>https://idp.edu/metadata</saml:Issuer><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
    <ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
  <ds:Reference URI="#pfxaa30421c-e921-072d-d75a-decd1a4ee918"><ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></ds:Transforms><ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/><ds:DigestValue>Dai+mpIJEcty+GeeC3mPKu23VbU=</ds:DigestValue></ds:Reference></ds:SignedInfo><ds:SignatureValue>YxL54HDcTIKAjamwVC+IddNkHxLniNnUSxm6kBhRXqpfYLT6sgllMiM8Ahyjhdq/0YxyUYZzwHiCvwD7xrSxgQ5s3wuLAnIt3uxL8zPQ032ZRPbKhZCFTxCQwLCX+ttoAzNziilcvGTkT27eLIkTcCx7sHmXIgg4RBwqVjKivSQZ66R9V9NjI8wmo/QikYt69PFIZaKsat0/VGHKizURAnr5iSA7qARTRGWK8WcMJR25B5TBKaxkGu6HIeWRaEDhPbD27V7UYTzx7qL00PafoUQ+5U+Baxc1ST/4NOL+Go29LFgEb0DaggBJh9x2j5dIc1MjxXBDKJETE3kEDNb6MQ==</ds:SignatureValue>
<ds:KeyInfo><ds:X509Data><ds:X509Certificate>MIID5zCCAs+gAwIBAgIJAL8E2GQ671hSMA0GCSqGSIb3DQEBCwUAMIGIMQswCQYDVQQGEwJOTDEQMA4GA1UECAwHWmVlbGFuZDETMBEGA1UEBwwKVmxpc3NpbmdlbjETMBEGA1UECgwKSWJ1aWxkaW5nczELMAkGA1UECwwCSVQxDzANBgNVBAMMBmlkcC5ubDEfMB0GCSqGSIb3DQEJARYQdGVzdEBleGFtcGxlLmNvbTAgFw0xODA4MjAwOTM2NDVaGA8yMTE4MDcyNzA5MzY0NVowgYgxCzAJBgNVBAYTAk5MMRAwDgYDVQQIDAdaZWVsYW5kMRMwEQYDVQQHDApWbGlzc2luZ2VuMRMwEQYDVQQKDApJYnVpbGRpbmdzMQswCQYDVQQLDAJJVDEPMA0GA1UEAwwGaWRwLm5sMR8wHQYJKoZIhvcNAQkBFhB0ZXN0QGV4YW1wbGUuY29tMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvmsJW2d+48UMs/r7BLQZWn4v7bXAQZxlAy5OE+SXZyafgiI22pJF1qRE6MiqYoVsJq4F7qfCv/8pFjUmnVIomaeRT88MI4nGrlEVL12SLzBzM/ftSrTP0FhoM8dmAW9VJUghjp7UYm7SFuPok0HpOV9A/5Z6nrkZ/mnEo24CDcFr5V06rg3fPofYD6FN1aIaYoNu3gtUo9rnS1fDw4m1fj5+X1VGKTqmvKpHTBS5cWZjvlU0Fw0N4tiJmJSq3sCclPvVXBXKcJeBhKA/jEurVTsqWerNfZ8O8iolwuXQLyva0ugvSRU8G0zIJMINUIAi03ulI978D1Pq0ZYIbcKxKwIDAQABo1AwTjAdBgNVHQ4EFgQUN6TQ5gwRg6ZFrjl8YuVssW59+RkwHwYDVR0jBBgwFoAUN6TQ5gwRg6ZFrjl8YuVssW59+RkwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQsFAAOCAQEARPxXmwswRaUg0jh6v9Q6IwOhPrPxw3uY1KxSX/+cfjPKVJXyfQQshJne4rAfLbBZPEmAbXi8xmMQvk7SjFsq8EjjGKCw9D5YikeucstxC6Ri4pRQcZcTi/o7Q06eKi2LFC7UM0RXIKBtCSUI5wYRzExFW0sUcTnfeCNdf0lk4fRVMrvccF04F7QANDcQSeMbdSSZZrUrEGYR+hGLypsq/7p5eSxgs8ooJhBgLULzOhfYz6qnCi5AHxvjKxogyvaDIdUvJUY/eU5xWpQT2IEW594tF876NNhnjPmZSZrGzRwkH2T0F9RZEf9sEjtd2tbbETjAzsBNNMOsGdLr2vO3WQ==</ds:X509Certificate></ds:X509Data></ds:KeyInfo></ds:Signature>
        
        <saml:Subject>
            <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">
                724cca6778a1d3db16b65c40d4c378d011f220be
            </saml:NameID>
            <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
                <saml:SubjectConfirmationData IssueInstant="2014-10-22T11:09:59Z" Recipient="https://gateway.org/acs" InResponseTo="_mocked_generated_id"/>
            </saml:SubjectConfirmation>
        </saml:Subject>
        <saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z">
            <saml:AudienceRestriction>
                <saml:Audience>https://gateway.org/metadata</saml:Audience>
            </saml:AudienceRestriction>
        </saml:Conditions>
        <saml:AuthnStatement AuthnInstant="2014-10-22T11:07:07Z" SessionNotOnOrAfter="2014-10-22T19:07:07Z" SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b">
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
                    <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">
                        urn:collab:person:example.edu:jdoe
                    </saml:NameID>
                </saml:AttributeValue>
            </saml:Attribute>
            <saml:Attribute Name="urn:mace:surf.nl:attribute-def:internal-collabPersonId" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
                <saml:AttributeValue xsi:type="xs:string">urn:collab:person:example.edu:jdoe</saml:AttributeValue>
            </saml:Attribute>
        </saml:AttributeStatement>
    </saml:Assertion>
</samlp:Response>';

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
            'surfnet/gateway/requestrelay_state' => 'relay_state',
            'surfnet/gateway/requestresponse_controller' => 'Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController::respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'gateway.proxy.response_context',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestgateway_request_id' => '_mocked_generated_id',
        ]);

        $this->mockPostBinding($samlResponseXml);

        $samlResponse = base64_encode($samlResponseXml);

        $httpRequest = Request::create('idp.nl/sso-url');
        $httpRequest->request->set('SAMLResponse', $samlResponse);

        // Consume assertion
        $this->gatewayConsumeAssertionService->consumeAssertion($httpRequest, $this->responseContext);

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
                'Forwarding to second factor controller for loa determination and handling',
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
            'surfnet/gateway/requestresponse_controller' => 'Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController::respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'gateway.proxy.response_context',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestgateway_request_id' => '_mocked_generated_id',
            'surfnet/gateway/requestname_id' => 'urn:collab:person:example.edu:jdoe',
            'surfnet/gateway/requestauthenticating_idp' => 'https://proxied-idp.edu/',
            'surfnet/gateway/requestresponse_assertion' => '<?xml version="1.0" encoding="UTF-8"?>
<saml:Assertion xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="pfxaa30421c-e921-072d-d75a-decd1a4ee918" Version="2.0" IssueInstant="2014-10-22T11:09:59Z"><saml:Issuer>https://idp.edu/metadata</saml:Issuer><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID><saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><saml:SubjectConfirmationData Recipient="https://gateway.org/acs" InResponseTo="_mocked_generated_id"/></saml:SubjectConfirmation></saml:Subject><saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z"><saml:AudienceRestriction><saml:Audience>https://gateway.org/metadata</saml:Audience></saml:AudienceRestriction></saml:Conditions><saml:AuthnStatement AuthnInstant="2014-10-22T11:07:07Z" SessionNotOnOrAfter="2014-10-22T19:07:07Z" SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b"><saml:AuthnContext><saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef><saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority></saml:AuthnContext></saml:AuthnStatement><saml:AttributeStatement><saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue></saml:Attribute><saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID></saml:AttributeValue></saml:Attribute><saml:Attribute Name="urn:mace:surf.nl:attribute-def:internal-collabPersonId" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"><saml:AttributeValue xsi:type="xs:string">urn:collab:person:example.edu:jdoe</saml:AttributeValue></saml:Attribute></saml:AttributeStatement></saml:Assertion>
',
        ], $this->getSessionData('attributes'));
    }

    public function test_it_rejects_nameidless_responses(): void
    {
        // When the IdP did not add a subject nameid and also skips on the internal-collabPersonId. The assertion
        // is a 'valid' message according to our SAML processor (SAML2 library). But we can't process it, in
        // Gateway due to the lack of an identity id to match SF tokens with.
        $samlResponseXml = '<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="CORTO1111111111222222222233333333334444444444" Version="2.0" IssueInstant="2014-10-22T11:09:59Z" Destination="https://gateway.org/acs" InResponseTo="_mocked_generated_id">
    <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
    <samlp:Status>
        <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
    </samlp:Status>
    <saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/001/XMLSchema" ID="pfxaa30421c-e921-072d-d75a-decd1a4ee918" Version="2.0" IssueInstant="2014-10-22T11:09:59Z">
        <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
        <saml:Subject>
            
            <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
                <saml:SubjectConfirmationData IssueInstant="2014-10-22T11:09:59Z" Recipient="https://gateway.org/acs" InResponseTo="_mocked_generated_id"/>
            </saml:SubjectConfirmation>
        </saml:Subject>
        <saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z">
            <saml:AudienceRestriction>
                <saml:Audience>https://gateway.org/metadata</saml:Audience>
            </saml:AudienceRestriction>
        </saml:Conditions>
        <saml:AuthnStatement AuthnInstant="2014-10-22T11:07:07Z" SessionNotOnOrAfter="2014-10-22T19:07:07Z" SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b">
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
                    <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">
                        urn:collab:person:example.edu:jdoe
                    </saml:NameID>
                </saml:AttributeValue>
            </saml:Attribute>
        </saml:AttributeStatement>
    </saml:Assertion>
</samlp:Response>';

        $this->mockSessionData('_sf2_attributes', [
            'surfnet/gateway/requestrequest_id' => '_123456789012345678901234567890123456789012',
            'surfnet/gateway/requestservice_provider' => 'https://sp.com/metadata',
            'surfnet/gateway/requestassertion_consumer_service_url' => 'https://sp.com/acs',
            'surfnet/gateway/requestrelay_state' => 'relay_state',
            'surfnet/gateway/requestresponse_controller' => 'Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController::respond',
            'surfnet/gateway/requestresponse_context_service_id' => 'gateway.proxy.response_context',
            'surfnet/gateway/requestloa_identifier' => 'http://stepup.example.com/assurance/loa2',
            'surfnet/gateway/requestgateway_request_id' => '_mocked_generated_id',
        ]);

        $this->mockPostBinding($samlResponseXml);

        $samlResponse = base64_encode($samlResponseXml);

        $httpRequest = Request::create('idp.nl/sso-url');
        $httpRequest->request->set('SAMLResponse', $samlResponse);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve an identifier from internalCollabPersonId or the Subject NameId');
        $this->gatewayConsumeAssertionService->consumeAssertion($httpRequest, $this->responseContext);
    }

    /**
     * @param array $idpConfiguration
     * @param array $spConfiguration
     * @param int $now
     */
    private function initGatewayService(array $idpConfiguration, array $spConfiguration, DateTime $now): void
    {
        $session = new Session($this->sessionStorage);
        $requestStackMock = $this->createMock(RequestStack::class);
        $requestStackMock->method('getSession')->willReturn($session);

        $this->stateHandler = new ProxyStateHandler($requestStackMock, 'surfnet/gateway/request');
        $samlLogger = new SamlAuthenticationLogger($this->logger);

        $hostedServiceProvider = new ServiceProvider($spConfiguration);
        $this->remoteIdp = new IdentityProvider($idpConfiguration);
        $this->postBinding = Mockery::mock(PostBinding::class);
        $this->samlEntityService = Mockery::mock(SamlEntityService::class);

        $this->responseContext = new ResponseContext(
            $this->remoteIdp,
            $this->samlEntityService,
            $this->stateHandler,
            $this->logger,
            $now
        );

        $this->gatewayConsumeAssertionService = new ConsumeAssertionService(
            $this->postBinding,
            $samlLogger,
            $hostedServiceProvider,
            $this->remoteIdp
        );
    }

    /**
     * @param string $samlResponseXml
     */
    private function mockPostBinding($samlResponseXml): void
    {
        $asXml = DOMDocumentFactory::fromString($samlResponseXml);

        $response = new Response($asXml->documentElement);
        $assertion = $response->getAssertions()[0];

        $this->postBinding->shouldReceive('processResponse')
            ->andReturn($assertion);
    }
}
