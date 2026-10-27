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

namespace Surfnet\StepupGateway\GatewayBundle\Saml;

use DOMDocument;
use DOMElement;
use Surfnet\SamlBundle\SAML2\Extensions\Chunk;
use Surfnet\SamlBundle\SAML2\Extensions\Extensions;
use Surfnet\SamlBundle\SAML2\Extensions\ServiceNameFormatter;

class UiInfoExtensionMapper
{
    private const string MDUI_NAMESPACE = 'urn:oasis:names:tc:SAML:metadata:ui';
    private const string MDUI_PREFIX = 'mdui';

    private const int MAX_DISPLAY_NAMES = 10;
    private const int MAX_LANG_LENGTH = 35;

    private const int MAX_VALUE_LENGTH = 1024;

    /**
     * Values are sanitized at parse time because they can reach session state before any write.
     *
     * @return DisplayName[]
     */
    public function read(Extensions $extensions): array
    {
        $chunks = $extensions->getChunks();
        if (!isset($chunks['UIInfo'])) {
            return [];
        }

        $displayNames = [];
        $uiInfoElement = $chunks['UIInfo']->getValue();

        foreach ($uiInfoElement->childNodes as $child) {
            if (!($child instanceof DOMElement)) {
                continue;
            }
            $displayName = $this->sanitizedDisplayNameFrom($child);
            if ($displayName === null) {
                continue;
            }
            $displayNames[] = $displayName;
            if (count($displayNames) >= self::MAX_DISPLAY_NAMES) {
                break;
            }
        }

        return $displayNames;
    }

    private function sanitizedDisplayNameFrom(DOMElement $child): ?DisplayName
    {
        if ($child->localName !== 'DisplayName' || $child->namespaceURI !== self::MDUI_NAMESPACE) {
            return null;
        }
        $lang = $child->getAttribute('xml:lang');
        $value = $child->textContent;
        if (!$this->isSamlValid($lang, $value)) {
            return null;
        }
        $sanitizedLang = ServiceNameFormatter::sanitizeLang($lang);
        $sanitizedValue = ServiceNameFormatter::format($value);
        if ($sanitizedLang === '' || $sanitizedValue === '') {
            return null;
        }
        return new DisplayName($sanitizedLang, $sanitizedValue);
    }

    private function isSamlValid(string $lang, string $value): bool
    {
        return $lang !== '' && $value !== ''
            && strlen($lang) <= self::MAX_LANG_LENGTH
            && strlen($value) <= self::MAX_VALUE_LENGTH;
    }

    /**
     * Replaces mdui:UIInfo with $displayName (or strips it when null) and keeps all other chunks.
     * $original is never mutated, because the inbound AuthnRequest owns it.
     */
    public function applyTo(Extensions $original, ?DisplayName $displayName): Extensions
    {
        $result = new Extensions();
        foreach ($original->getChunks() as $name => $chunk) {
            if ($name === 'UIInfo') {
                continue;
            }
            $result->addChunk($chunk);
        }

        if ($displayName === null) {
            return $result;
        }

        $result->addChunk($this->buildUiInfoChunk($displayName));
        return $result;
    }

    private function buildUiInfoChunk(DisplayName $displayName): Chunk
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $uiInfo = $doc->createElementNS(self::MDUI_NAMESPACE, self::MDUI_PREFIX . ':UIInfo');
        $doc->appendChild($uiInfo);

        $element = $doc->createElementNS(self::MDUI_NAMESPACE, self::MDUI_PREFIX . ':DisplayName');
        $element->setAttribute('xml:lang', ServiceNameFormatter::sanitizeLang($displayName->lang));
        $element->textContent = ServiceNameFormatter::format($displayName->value);
        $uiInfo->appendChild($element);

        return new Chunk('UIInfo', self::MDUI_NAMESPACE, $doc->documentElement);
    }
}
