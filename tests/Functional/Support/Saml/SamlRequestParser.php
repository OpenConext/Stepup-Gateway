<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support\Saml;

use DOMDocument;
use DOMXPath;
use RuntimeException;

/**
 * Parses a SAML AuthnRequest sent by the gateway (for example to a GSSP).
 */
class SamlRequestParser
{
    private DOMDocument $document;
    private DOMXPath $xpath;

    public function __construct(string $xml)
    {
        $this->document = new DOMDocument();
        $this->document->loadXML($xml);

        $this->xpath = new DOMXPath($this->document);
        $this->xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
        $this->xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
    }

    /**
     * Create a parser from a URL (or query string) using the HTTP-Redirect binding.
     */
    public static function fromRedirectUrl(string $url): self
    {
        $query = parse_url($url, PHP_URL_QUERY) ?? $url;
        parse_str($query, $params);

        if (!isset($params['SAMLRequest'])) {
            throw new RuntimeException(sprintf('No SAMLRequest found in "%s"', $url));
        }

        $xml = gzinflate(base64_decode($params['SAMLRequest'], true));
        if ($xml === false) {
            throw new RuntimeException('Unable to inflate the SAMLRequest');
        }

        return new self($xml);
    }

    public function getId(): string
    {
        return $this->document->documentElement->getAttribute('ID');
    }

    public function getIssuer(): ?string
    {
        return $this->xpath->query('/samlp:AuthnRequest/saml:Issuer')->item(0)?->textContent;
    }

    public function getRawXml(): string
    {
        return $this->document->saveXML();
    }
}
