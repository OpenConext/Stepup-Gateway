<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support\Saml;

use DateTimeImmutable;
use DOMDocument;
use DOMXPath;

/**
 * Parses and provides assertions for SAML responses.
 */
class SamlResponseParser
{
    private DOMDocument $document;
    private DOMXPath $xpath;

    public function __construct(private readonly string $base64Response)
    {
        $xml = base64_decode($base64Response);
        $this->document = new DOMDocument();
        $this->document->loadXML($xml);

        $this->xpath = new DOMXPath($this->document);
        $this->xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
        $this->xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
        $this->xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    }

    /**
     * The response exactly as received (the value of the SAMLResponse parameter)
     */
    public function getBase64Response(): string
    {
        return $this->base64Response;
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

    public function hasAssertion(): bool
    {
        return $this->xpath->query('/samlp:Response/saml:Assertion')->length > 0;
    }

    public function getSubjectConfirmationInResponseTo(): ?string
    {
        return $this->getAttribute('//saml:SubjectConfirmationData/@InResponseTo');
    }

    public function getResponseIssueInstant(): ?DateTimeImmutable
    {
        return $this->getTime('/samlp:Response/@IssueInstant');
    }

    public function getAssertionIssueInstant(): ?DateTimeImmutable
    {
        return $this->getTime('/samlp:Response/saml:Assertion/@IssueInstant');
    }

    public function getAuthnInstant(): ?DateTimeImmutable
    {
        return $this->getTime('//saml:AuthnStatement/@AuthnInstant');
    }

    public function getSessionNotOnOrAfter(): ?DateTimeImmutable
    {
        return $this->getTime('//saml:AuthnStatement/@SessionNotOnOrAfter');
    }

    public function getConditionsNotBefore(): ?DateTimeImmutable
    {
        return $this->getTime('//saml:Conditions/@NotBefore');
    }

    public function getConditionsNotOnOrAfter(): ?DateTimeImmutable
    {
        return $this->getTime('//saml:Conditions/@NotOnOrAfter');
    }

    public function getSubjectConfirmationNotOnOrAfter(): ?DateTimeImmutable
    {
        return $this->getTime('//saml:SubjectConfirmationData/@NotOnOrAfter');
    }

    private function getAttribute(string $query): ?string
    {
        return $this->xpath->query($query)->item(0)?->nodeValue;
    }

    private function getTime(string $query): ?DateTimeImmutable
    {
        $value = $this->getAttribute($query);

        return $value === null ? null : new DateTimeImmutable($value);
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
