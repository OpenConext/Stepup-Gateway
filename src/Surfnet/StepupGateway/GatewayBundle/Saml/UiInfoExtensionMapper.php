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
    private const MDUI_NAMESPACE = 'urn:oasis:names:tc:SAML:metadata:ui';
    private const MDUI_PREFIX = 'mdui';

    // Defensive parsing bounds, not derived from any spec — cap how much an incoming
    // AuthnRequest's UIInfo extension can make this parser do.
    private const MAX_DISPLAY_NAMES = 10;
    private const MAX_LANG_LENGTH = 35;

    // MAX_VALUE_LENGTH is SAML 2.0's own maximum string length for this element type, not
    // specific to this feature.
    private const MAX_VALUE_LENGTH = 1024;

    /**
     * Extracts DisplayName entries from an Extensions object's mdui:UIInfo chunk, if present.
     * The RFC (OpenConext/Stepup-Gateway#587) expects senders to already resolve to one
     * locale-matched name before sending, but this parser stays defensive and doesn't assume that.
     *
     * Sanitized here, at parse time, via the same ServiceNameFormatter used when writing —
     * not only on write. Between AuthnRequest arrival and any eventual write, these values can
     * sit in session state (e.g. for SMS/Yubikey screens that read the session directly rather
     * than going through applyTo()); sanitizing only on write would leave raw, attacker-supplied
     * text reachable from any such read path.
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
            if ($child->localName !== 'DisplayName' || $child->namespaceURI !== self::MDUI_NAMESPACE) {
                continue;
            }
            $lang = $child->getAttribute('xml:lang');
            $value = $child->textContent;
            if (!$this->isSamlValid($lang, $value)) {
                continue;
            }
            $sanitizedLang = ServiceNameFormatter::sanitizeLang($lang);
            $sanitizedValue = ServiceNameFormatter::format($value);
            if ($sanitizedLang === '' || $sanitizedValue === '') {
                continue;
            }
            $displayNames[] = new DisplayName($sanitizedLang, $sanitizedValue);
            if (count($displayNames) >= self::MAX_DISPLAY_NAMES) {
                break;
            }
        }

        return $displayNames;
    }

    // Structural sanity only (non-empty, within length bounds) — independent of whether
    // Gateway will actually choose to display this particular name.
    private function isSamlValid(string $lang, string $value): bool
    {
        return $lang !== '' && $value !== ''
            && strlen($lang) <= self::MAX_LANG_LENGTH
            && strlen($value) <= self::MAX_VALUE_LENGTH;
    }

    /**
     * $displayName null strips any existing mdui:UIInfo; given, it's sanitized into a fresh
     * one. Every other chunk (e.g. gssp:UserAttributes) is preserved either way.
     *
     * $original may be owned by another object (e.g. an inbound AuthnRequest), so it's never
     * mutated in place — that would leave the owner's Extensions wrapper out of sync with its
     * underlying SAML2 XML.
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

    // read() already sanitizes; re-sanitizing here is defense in depth for DisplayName
    // instances built from other sources (e.g. Middleware's locale map), not redundant work.
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
