<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support\Saml;

use DOMDocument;
use DOMXPath;

/**
 * Parses and provides assertions for SAML responses.
 */
class SamlResponseParser
{
    private DOMDocument $document;
    private DOMXPath $xpath;

    public function __construct(string $base64Response)
    {
        $xml = base64_decode($base64Response);
        $this->document = new DOMDocument();
        $this->document->loadXML($xml);

        $this->xpath = new DOMXPath($this->document);
        $this->xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
        $this->xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
        $this->xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    }

    public function getStatusCode(): string
    {
        $node = $this->xpath->query('//samlp:StatusCode')->item(0);
        return $node?->getAttribute('Value') ?? '';
    }

    public function getStatusMessage(): ?string
    {
        $node = $this->xpath->query('//samlp:StatusMessage')->item(0);
        return $node?->textContent;
    }

    public function getAudience(): ?string
    {
        $node = $this->xpath->query('//saml:Audience')->item(0);
        return $node?->textContent;
    }

    public function getNameId(): ?string
    {
        $node = $this->xpath->query('//saml:NameID')->item(0);
        return $node?->textContent;
    }

    public function getInResponseTo(): ?string
    {
        $node = $this->xpath->query('//samlp:Response')->item(0);
        return $node?->getAttribute('InResponseTo');
    }

    public function getAuthnContextClassRef(): ?string
    {
        $node = $this->xpath->query('//saml:AuthnContextClassRef')->item(0);
        return $node?->textContent;
    }

    public function isSigned(): bool
    {
        return $this->xpath->query('//ds:Signature')->length > 0;
    }

    public function getRawXml(): string
    {
        return $this->document->saveXML();
    }
}
