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
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;

class UiInfoExtensionHelper
{
    private const MDUI_NAMESPACE = 'urn:oasis:names:tc:SAML:metadata:ui';
    private const MDUI_PREFIX = 'mdui';

    private const MAX_DISPLAY_NAMES = 10;
    private const MAX_LANG_LENGTH = 35;
    private const MAX_VALUE_LENGTH = 1024;

    /**
     * Parse UIInfo display names from an AuthnRequest and store them in state.
     *
     * Always overwrites the stored display names, so a request without UIInfo
     * clears any display names left over from a previous request in the same session.
     */
    public static function parseAndStore(ReceivedAuthnRequest $request, ProxyStateHandler $stateHandler): void
    {
        $chunks = $request->getExtensions()->getChunks();
        $displayNames = isset($chunks['UIInfo'])
            ? self::parseDisplayNamesFromChunk($chunks['UIInfo'])
            : [];
        $stateHandler->setDisplayNamesFromRequest(...$displayNames);
    }

    /**
     * Extract DisplayName entries from a UIInfo Chunk.
     *
     * @return DisplayName[]
     */
    public static function parseDisplayNamesFromChunk(Chunk $chunk): array
    {
        $displayNames = [];
        $uiInfoElement = $chunk->getValue();

        foreach ($uiInfoElement->childNodes as $child) {
            if (!($child instanceof DOMElement)) {
                continue;
            }
            if ($child->localName !== 'DisplayName' || $child->namespaceURI !== self::MDUI_NAMESPACE) {
                continue;
            }
            $lang = $child->getAttribute('xml:lang');
            $value = $child->textContent;
            if ($lang === '' || $value === ''
                || strlen($lang) > self::MAX_LANG_LENGTH
                || strlen($value) > self::MAX_VALUE_LENGTH
            ) {
                continue;
            }
            $displayNames[] = new DisplayName($lang, $value);
            if (count($displayNames) >= self::MAX_DISPLAY_NAMES) {
                break;
            }
        }

        return $displayNames;
    }

    /**
     * Build an Extensions object containing a mdui:UIInfo with the given display names.
     *
     * @param DisplayName[] $displayNames
     */
    public static function buildExtensionsWithUiInfo(array $displayNames): Extensions
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $uiInfo = $doc->createElementNS(self::MDUI_NAMESPACE, self::MDUI_PREFIX . ':UIInfo');
        $doc->appendChild($uiInfo);

        foreach ($displayNames as $entry) {
            $displayName = $doc->createElementNS(self::MDUI_NAMESPACE, self::MDUI_PREFIX . ':DisplayName');
            $displayName->setAttribute('xml:lang', $entry->lang);
            $displayName->textContent = $entry->value;
            $uiInfo->appendChild($displayName);
        }

        $chunk = new Chunk('UIInfo', self::MDUI_NAMESPACE, $doc->documentElement);
        $extensions = new Extensions();
        $extensions->addChunk($chunk);

        return $extensions;
    }
}
