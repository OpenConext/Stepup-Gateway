<?php

/**
 * Copyright 2026 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Monolog\Processor;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\GatewayBundle\Monolog\Processor\RequestorEntityIdProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestorEntityIdProcessorTest extends TestCase
{
    private const ENTITY_ID = 'https://sp.example.org/metadata';

    private function createLogRecord(): LogRecord
    {
        return new LogRecord(
            new DateTimeImmutable(),
            'request',
            Level::Error,
            'Uncaught PHP Exception',
            [],
            ['request_id' => '12345']
        );
    }

    private function createAuthnRequestXml(string $issuer): string
    {
        return <<<XML
        <samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                            xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                            ID="_123" Version="2.0" IssueInstant="2026-11-12T00:00:00Z"
                            Destination="https://gateway.example.org/authentication/single-sign-on">
            <saml:Issuer>{$issuer}</saml:Issuer>
        </samlp:AuthnRequest>
        XML;
    }

    private function createRedirectBindingSamlRequest(string $xml): string
    {
        return base64_encode(gzdeflate($xml));
    }

    private function createPostBindingSamlRequest(string $xml): string
    {
        return base64_encode($xml);
    }

    private function createProcessorWithRequest(Request $request): RequestorEntityIdProcessor
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new RequestorEntityIdProcessor($requestStack);
    }

    public function test_does_nothing_when_no_request_present(): void
    {
        $processor = new RequestorEntityIdProcessor(new RequestStack());

        $processed = $processor($this->createLogRecord());

        $this->assertArrayNotHasKey('requestor_entity_id', $processed->extra);
    }

    public function test_adds_requestor_entity_id_from_redirect_binding_authn_request(): void
    {
        $samlRequest = $this->createRedirectBindingSamlRequest($this->createAuthnRequestXml(self::ENTITY_ID));
        $request = Request::create('/authentication/single-sign-on', 'GET', ['SAMLRequest' => $samlRequest]);

        $processed = $this->createProcessorWithRequest($request)($this->createLogRecord());

        $this->assertSame(self::ENTITY_ID, $processed->extra['requestor_entity_id']);
        $this->assertSame('12345', $processed->extra['request_id']);
    }

    public function test_adds_requestor_entity_id_from_post_binding_authn_request(): void
    {
        $samlRequest = $this->createPostBindingSamlRequest($this->createAuthnRequestXml(self::ENTITY_ID));
        $request = Request::create('/authentication/single-sign-on', 'POST', ['SAMLRequest' => $samlRequest]);

        $processed = $this->createProcessorWithRequest($request)($this->createLogRecord());

        $this->assertSame(self::ENTITY_ID, $processed->extra['requestor_entity_id']);
    }

    public function test_does_nothing_when_no_saml_request_present(): void
    {
        $request = Request::create('/some/page', 'GET');

        $processed = $this->createProcessorWithRequest($request)($this->createLogRecord());

        $this->assertArrayNotHasKey('requestor_entity_id', $processed->extra);
    }

    public function test_does_nothing_when_saml_request_is_not_valid_base64(): void
    {
        $request = Request::create('/authentication/single-sign-on', 'GET', ['SAMLRequest' => '!!!not-base64!!!']);

        $processed = $this->createProcessorWithRequest($request)($this->createLogRecord());

        $this->assertArrayNotHasKey('requestor_entity_id', $processed->extra);
    }

    public function test_does_nothing_when_saml_request_is_not_xml(): void
    {
        $samlRequest = $this->createRedirectBindingSamlRequest('this is not xml');
        $request = Request::create('/authentication/single-sign-on', 'GET', ['SAMLRequest' => $samlRequest]);

        $processed = $this->createProcessorWithRequest($request)($this->createLogRecord());

        $this->assertArrayNotHasKey('requestor_entity_id', $processed->extra);
    }

    public function test_does_nothing_when_saml_message_is_not_an_authn_request(): void
    {
        $xml = <<<XML
        <samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                        xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                        ID="_123" Version="2.0" IssueInstant="2026-11-12T00:00:00Z">
            <saml:Issuer>https://idp.example.org</saml:Issuer>
        </samlp:Response>
        XML;
        $samlRequest = $this->createRedirectBindingSamlRequest($xml);
        $request = Request::create('/assertion-consumer-service', 'POST', ['SAMLResponse' => $samlRequest]);

        $processed = $this->createProcessorWithRequest($request)($this->createLogRecord());

        $this->assertArrayNotHasKey('requestor_entity_id', $processed->extra);
    }

    public function test_parses_saml_request_only_once_per_request(): void
    {
        $samlRequest = $this->createRedirectBindingSamlRequest($this->createAuthnRequestXml(self::ENTITY_ID));
        $request = Request::create('/authentication/single-sign-on', 'GET', ['SAMLRequest' => $samlRequest]);
        $processor = $this->createProcessorWithRequest($request);

        $first = $processor($this->createLogRecord());
        $second = $processor($this->createLogRecord());

        $this->assertSame(self::ENTITY_ID, $first->extra['requestor_entity_id']);
        $this->assertSame(self::ENTITY_ID, $second->extra['requestor_entity_id']);
    }
}
