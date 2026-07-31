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
use Surfnet\SamlBundle\SAML2\Extensions\Chunk;
use Surfnet\SamlBundle\SAML2\Extensions\Extensions;
use Surfnet\StepupGateway\GatewayBundle\Saml\DisplayName;
use Surfnet\StepupGateway\GatewayBundle\Saml\UiInfoExtensionMapper;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;

class UiInfoExtensionMapperTest extends GatewaySamlTestCase
{
    private const MDUI_NAMESPACE = 'urn:oasis:names:tc:SAML:metadata:ui';

    private UiInfoExtensionMapper $mapper;

    public function setUp(): void
    {
        parent::setUp();
        $this->mapper = new UiInfoExtensionMapper();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_reads_display_names_from_a_ui_info_extension(): void
    {
        $extensions = $this->extensionsWithUiInfoChunk([
            'en' => 'Online learning environment',
            'nl' => 'Elektronische leeromgeving',
        ]);

        $displayNames = $this->mapper->read($extensions);

        $this->assertCount(2, $displayNames);
        $this->assertContainsEquals(new DisplayName('en', 'Online learning environment'), $displayNames);
        $this->assertContainsEquals(new DisplayName('nl', 'Elektronische leeromgeving'), $displayNames);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_empty_array_when_extensions_have_no_ui_info_chunk(): void
    {
        $this->assertSame([], $this->mapper->read(new Extensions()));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_empty_array_when_ui_info_has_no_display_names(): void
    {
        $displayNames = $this->mapper->read($this->extensionsWithUiInfoChunk([]));

        $this->assertSame([], $displayNames);
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

        $extensions = new Extensions();
        $extensions->addChunk(new Chunk('UIInfo', self::MDUI_NAMESPACE, $doc->documentElement));

        $result = $this->mapper->read($extensions);

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

        $extensions = new Extensions();
        $extensions->addChunk(new Chunk('UIInfo', self::MDUI_NAMESPACE, $doc->documentElement));

        $this->assertSame([], $this->mapper->read($extensions));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_limits_the_number_of_parsed_display_names(): void
    {
        $langToValue = [];
        for ($i = 0; $i < 25; $i++) {
            $langToValue['x-l' . $i] = 'Service ' . $i;
        }

        $displayNames = $this->mapper->read($this->extensionsWithUiInfoChunk($langToValue));

        $this->assertCount(10, $displayNames);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_skips_display_names_exceeding_length_limits(): void
    {
        $extensions = $this->extensionsWithUiInfoChunk([
            'en' => str_repeat('a', 1025),
            str_repeat('l', 36) => 'Too long lang',
            'nl' => 'Acceptable Service',
        ]);

        $displayNames = $this->mapper->read($extensions);

        $this->assertCount(1, $displayNames);
        $this->assertSame('nl', $displayNames[0]->lang);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_builds_a_ui_info_chunk_for_the_given_display_name(): void
    {
        $result = $this->mapper->applyTo(new Extensions(), new DisplayName('en', 'My Service'));

        $chunks = $result->getChunks();
        $this->assertArrayHasKey('UIInfo', $chunks);

        $uiInfoElement = $chunks['UIInfo']->getValue();
        $this->assertSame('UIInfo', $uiInfoElement->localName);
        $this->assertSame(self::MDUI_NAMESPACE, $uiInfoElement->namespaceURI);

        $this->assertSame(1, $uiInfoElement->childNodes->length);
        $displayNameNode = $uiInfoElement->childNodes->item(0);
        $this->assertSame('en', $displayNameNode->getAttribute('xml:lang'));
        $this->assertSame('My Service', $displayNameNode->textContent);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_roundtrips_a_display_name(): void
    {
        $input = new DisplayName('en', 'Round Trip Service');

        $extensions = $this->mapper->applyTo(new Extensions(), $input);
        $parsed = $this->mapper->read($extensions);

        $this->assertEquals([$input], $parsed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_strips_ui_info_when_given_no_display_name(): void
    {
        $withUiInfo = $this->mapper->applyTo(new Extensions(), new DisplayName('en', 'Should be stripped'));

        $stripped = $this->mapper->applyTo($withUiInfo, null);

        $this->assertArrayNotHasKey('UIInfo', $stripped->getChunks());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_preserves_other_chunks_when_applying_a_display_name(): void
    {
        $extensions = $this->extensionsWithUserAttributesChunk();

        $result = $this->mapper->applyTo($extensions, new DisplayName('en', 'New name'));

        $this->assertArrayHasKey('UIInfo', $result->getChunks());
        $this->assertArrayHasKey('UserAttributes', $result->getChunks());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_preserves_other_chunks_when_stripping_ui_info(): void
    {
        $extensions = $this->extensionsWithUserAttributesChunk();
        $withUiInfo = $this->mapper->applyTo($extensions, new DisplayName('en', 'Strip me'));

        $stripped = $this->mapper->applyTo($withUiInfo, null);

        $this->assertArrayNotHasKey('UIInfo', $stripped->getChunks());
        $this->assertArrayHasKey('UserAttributes', $stripped->getChunks());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_replaces_an_existing_ui_info_chunk_rather_than_appending_to_it(): void
    {
        $withOldName = $this->mapper->applyTo(new Extensions(), new DisplayName('en', 'Old name'));

        $withNewName = $this->mapper->applyTo($withOldName, new DisplayName('en', 'New name'));

        $uiInfoElement = $withNewName->getChunks()['UIInfo']->getValue();
        $this->assertSame(1, $uiInfoElement->childNodes->length);
        $this->assertSame('New name', $uiInfoElement->childNodes->item(0)->textContent);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_strips_control_characters_from_the_xml_lang_attribute(): void
    {
        $extensions = $this->mapper->applyTo(new Extensions(), new DisplayName("en\x01-GB", 'My Service'));
        $uiInfoElement = $extensions->getChunks()['UIInfo']->getValue();

        $this->assertSame('en-GB', $uiInfoElement->childNodes->item(0)->getAttribute('xml:lang'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_an_equivalent_extensions_object_when_there_is_nothing_to_strip(): void
    {
        $stripped = $this->mapper->applyTo(new Extensions(), null);

        $this->assertSame([], $stripped->getChunks());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_truncates_display_names_longer_than_max_characters(): void
    {
        $extensions = $this->mapper->applyTo(new Extensions(), new DisplayName('en', str_repeat('a', 45)));
        $uiInfoElement = $extensions->getChunks()['UIInfo']->getValue();

        $expected = str_repeat('a', 39) . "\u{2026}";
        $this->assertSame($expected, $uiInfoElement->childNodes->item(0)->textContent);
        $this->assertSame(40, mb_strlen($uiInfoElement->childNodes->item(0)->textContent));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sanitizes_whitespace_and_control_characters_in_display_names(): void
    {
        $extensions = $this->mapper->applyTo(new Extensions(), new DisplayName('en', "  My   \tService\x01 "));
        $uiInfoElement = $extensions->getChunks()['UIInfo']->getValue();

        $this->assertSame('My Service', $uiInfoElement->childNodes->item(0)->textContent);
    }

    private function extensionsWithUiInfoChunk(array $langToValue): Extensions
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

        $extensions = new Extensions();
        $extensions->addChunk(new Chunk('UIInfo', self::MDUI_NAMESPACE, $doc->documentElement));
        return $extensions;
    }

    private function extensionsWithUserAttributesChunk(): Extensions
    {
        $extensions = new Extensions();
        $doc = new DOMDocument('1.0', 'UTF-8');
        $element = $doc->createElementNS(
            'urn:mace:surf.nl:stepup:gssp-extensions',
            'gssp:UserAttributes'
        );
        $doc->appendChild($element);
        $extensions->addChunk(new Chunk(
            'UserAttributes',
            'urn:mace:surf.nl:stepup:gssp-extensions',
            $doc->documentElement
        ));
        return $extensions;
    }
}
