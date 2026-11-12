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

namespace Surfnet\StepupGateway\GatewayBundle\Monolog\Processor;

use DOMXPath;
use Monolog\LogRecord;
use SAML2\DOMDocumentFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * Adds the entityID of the requestor (the Issuer of an incoming AuthnRequest) to the log record's extra data.
 *
 * The Issuer is read from the raw, possibly untrusted SAMLRequest; when signature validation fails the
 * value is unverified, but it still identifies the claimed requestor in the logs.
 */
final class RequestorEntityIdProcessor
{
    private ?Request $lastRequest = null;

    private ?string $cachedEntityId = null;

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return $record;
        }

        if ($request !== $this->lastRequest) {
            $this->lastRequest = $request;
            $this->cachedEntityId = $this->extractRequestorEntityId($request);
        }

        if ($this->cachedEntityId !== null) {
            $record->extra['requestor_entity_id'] = $this->cachedEntityId;
        }

        return $record;
    }

    private function extractRequestorEntityId(Request $request): ?string
    {
        try {
            $samlRequest = $request->query->get('SAMLRequest') ?? $request->request->get('SAMLRequest');

            if (!is_string($samlRequest) || $samlRequest === '') {
                return null;
            }

            $decoded = base64_decode($samlRequest, true);

            if ($decoded === false) {
                return null;
            }

            // Redirect binding messages are deflated, POST binding messages are not
            $inflated = $this->inflate($decoded);
            $xml = $inflated ?? $decoded;

            $document = DOMDocumentFactory::fromString($xml);

            if (!$document->firstChild instanceof \DOMElement || $document->firstChild->localName !== 'AuthnRequest') {
                return null;
            }

            $issuer = (new DOMXPath($document))
                ->query('./*[local-name()="Issuer"]', $document->firstChild)
                ->item(0);

            $entityId = trim($issuer?->textContent ?? '');

            return $entityId !== '' ? $entityId : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function inflate(string $data): ?string
    {
        // Catch any errors gzinflate triggers
        set_error_handler(static function (): bool {
            return true;
        });

        try {
            $inflated = gzinflate($data);
        } finally {
            restore_error_handler();
        }

        return $inflated === false ? null : $inflated;
    }
}
