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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Saml;

use DOMDocument;
use Mockery;
use Surfnet\SamlBundle\SAML2\Extensions\Chunk;
use Surfnet\StepupGateway\GatewayBundle\Saml\DisplayName;
use Surfnet\StepupGateway\GatewayBundle\Saml\UiInfoExtensionHelper;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;

class UiInfoExtensionHelperTest extends GatewaySamlTestCase
{
    private const MDUI_NAMESPACE = 'urn:oasis:names:tc:SAML:metadata:ui';

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_parses_display_names_from_a_ui_info_chunk(): void
    {
        $chunk = $this->buildUiInfoChunk([
            'en' => 'Online learning environment',
            'nl' => 'Elektronische leeromgeving',
        ]);

        $displayNames = UiInfoExtensionHelper::parseDisplayNamesFromChunk($chunk);

        $this->assertCount(2, $displayNames);
        $this->assertContainsEquals(new DisplayName('en', 'Online learning environment'), $displayNames);
        $this->assertContainsEquals(new DisplayName('nl', 'Elektronische leeromgeving'), $displayNames);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_empty_array_when_ui_info_has_no_display_names(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $uiInfo = $doc->createElementNS(self::MDUI_NAMESPACE, 'mdui:UIInfo');
        $doc->appendChild($uiInfo);
        $chunk = new Chunk('UIInfo', self::MDUI_NAMESPACE, $doc->documentElement);

        $displayNames = UiInfoExtensionHelper::parseDisplayNamesFromChunk($chunk);

        $this->assertSame([], $displayNames);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_builds_extensions_with_ui_info_from_display_names(): void
    {
        $input = [
            new DisplayName('en', 'My Service'),
            new DisplayName('nl', 'Mijn Dienst'),
        ];

        $extensions = UiInfoExtensionHelper::buildExtensionsWithUiInfo($input);

        $chunks = $extensions->getChunks();
        $this->assertArrayHasKey('UIInfo', $chunks);

        $uiInfoElement = $chunks['UIInfo']->getValue();
        $this->assertSame('UIInfo', $uiInfoElement->localName);
        $this->assertSame(self::MDUI_NAMESPACE, $uiInfoElement->namespaceURI);

        $displayNameNodes = $uiInfoElement->childNodes;
        $this->assertSame(2, $displayNameNodes->length);

        $first = $displayNameNodes->item(0);
        $this->assertSame('en', $first->getAttribute('xml:lang'));
        $this->assertSame('My Service', $first->textContent);

        $second = $displayNameNodes->item(1);
        $this->assertSame('nl', $second->getAttribute('xml:lang'));
        $this->assertSame('Mijn Dienst', $second->textContent);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_roundtrips_display_names(): void
    {
        $input = [
            new DisplayName('en', 'Round Trip Service'),
        ];

        $extensions = UiInfoExtensionHelper::buildExtensionsWithUiInfo($input);
        $chunks = $extensions->getChunks();
        $chunk = $chunks['UIInfo'];

        $parsed = UiInfoExtensionHelper::parseDisplayNamesFromChunk($chunk);

        $this->assertEquals($input, $parsed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_skips_display_names_with_empty_lang_attribute(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $uiInfo = $doc->createElementNS(self::MDUI_NAMESPACE, 'mdui:UIInfo');
        $doc->appendChild($uiInfo);

        $blank = $doc->createElementNS(self::MDUI_NAMESPACE, 'mdui:DisplayName');
        $blank->setAttribute('xml:lang', '');
        $blank->textContent = 'Some Service';
        $uiInfo->appendChild($blank);

        $valid = $doc->createElementNS(self::MDUI_NAMESPACE, 'mdui:DisplayName');
        $valid->setAttribute('xml:lang', 'en');
        $valid->textContent = 'Some Service';
        $uiInfo->appendChild($valid);

        $chunk = new Chunk('UIInfo', self::MDUI_NAMESPACE, $doc->documentElement);
        $result = UiInfoExtensionHelper::parseDisplayNamesFromChunk($chunk);

        $this->assertCount(1, $result);
        $this->assertSame('en', $result[0]->lang);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_skips_elements_from_other_namespaces(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $uiInfo = $doc->createElementNS(self::MDUI_NAMESPACE, 'mdui:UIInfo');
        $doc->appendChild($uiInfo);

        $foreign = $doc->createElementNS('urn:some:other:namespace', 'other:DisplayName');
        $foreign->setAttribute('xml:lang', 'en');
        $foreign->textContent = 'Should be ignored';
        $uiInfo->appendChild($foreign);

        $chunk = new Chunk('UIInfo', self::MDUI_NAMESPACE, $doc->documentElement);
        $result = UiInfoExtensionHelper::parseDisplayNamesFromChunk($chunk);

        $this->assertSame([], $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function parse_and_store_does_nothing_when_no_ui_info_chunk_present(): void
    {
        $storage = new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage();
        $session = new \Symfony\Component\HttpFoundation\Session\Session($storage);
        $requestStack = \Mockery::mock(\Symfony\Component\HttpFoundation\RequestStack::class);
        $requestStack->shouldReceive('getSession')->andReturn($session);
        $stateHandler = new \Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler($requestStack, 'surfnet/gateway/request');

        $request = \Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest::from('<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_test" Version="2.0" IssueInstant="2017-04-18T16:35:32Z" Destination="https://example.com" AssertionConsumerServiceURL="https://example.com/acs" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://example.com</saml:Issuer></samlp:AuthnRequest>');

        UiInfoExtensionHelper::parseAndStore($request, $stateHandler);

        $this->assertSame([], $stateHandler->getDisplayNamesFromRequest());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function parse_and_store_does_nothing_when_ui_info_has_no_valid_display_names(): void
    {
        $storage = new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage();
        $session = new \Symfony\Component\HttpFoundation\Session\Session($storage);
        $requestStack = \Mockery::mock(\Symfony\Component\HttpFoundation\RequestStack::class);
        $requestStack->shouldReceive('getSession')->andReturn($session);
        $stateHandler = new \Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler($requestStack, 'surfnet/gateway/request');

        $request = \Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest::from('<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" ID="_test2" Version="2.0" IssueInstant="2017-04-18T16:35:32Z" Destination="https://example.com" AssertionConsumerServiceURL="https://example.com/acs" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://example.com</saml:Issuer><samlp:Extensions><mdui:UIInfo><mdui:DisplayName xml:lang="">Empty lang</mdui:DisplayName></mdui:UIInfo></samlp:Extensions></samlp:AuthnRequest>');

        UiInfoExtensionHelper::parseAndStore($request, $stateHandler);

        $this->assertSame([], $stateHandler->getDisplayNamesFromRequest());
    }

    private function buildUiInfoChunk(array $langToValue): Chunk
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $uiInfo = $doc->createElementNS(self::MDUI_NAMESPACE, 'mdui:UIInfo');
        $doc->appendChild($uiInfo);

        foreach ($langToValue as $lang => $value) {
            $displayName = $doc->createElementNS(self::MDUI_NAMESPACE, 'mdui:DisplayName');
            $displayName->setAttribute('xml:lang', $lang);
            $displayName->textContent = $value;
            $uiInfo->appendChild($displayName);
        }

        return new Chunk('UIInfo', self::MDUI_NAMESPACE, $doc->documentElement);
    }
}
