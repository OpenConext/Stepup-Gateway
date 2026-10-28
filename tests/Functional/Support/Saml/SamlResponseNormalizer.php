<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support\Saml;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * Normalizes a SAML Response so it can be compared with a baseline Response.
 *
 * The values that differ between every Response are replaced by a placeholder, all other XML is left as-is
 * (including the signing certificate), and the result is pretty printed to get readable diffs:
 *  - The IDs of the Response and Assertion. A ds:Reference to one of them keeps pointing at the right placeholder.
 *  - InResponseTo, SessionIndex and all timestamps (IssueInstant, NotBefore, NotOnOrAfter, AuthnInstant, ...)
 *  - The ds:DigestValue and ds:SignatureValue. Use SamlSignatureVerifier to verify the signature.
 */
class SamlResponseNormalizer
{
    private const NS_SAMLP = 'urn:oasis:names:tc:SAML:2.0:protocol';
    private const NS_SAML = 'urn:oasis:names:tc:SAML:2.0:assertion';
    private const NS_DS = 'http://www.w3.org/2000/09/xmldsig#';

    private const TIME_ATTRIBUTES = ['IssueInstant', 'NotBefore', 'NotOnOrAfter', 'AuthnInstant', 'SessionNotOnOrAfter'];

    public function normalize(string $xml): string
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        if (!$document->loadXML($xml)) {
            throw new RuntimeException('The SAML Response is not valid XML');
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('samlp', self::NS_SAMLP);
        $xpath->registerNamespace('saml', self::NS_SAML);
        $xpath->registerNamespace('ds', self::NS_DS);

        $this->replaceIds($xpath, '/samlp:Response', '__RESPONSE_ID__');
        $this->replaceIds($xpath, '//saml:Assertion', '__ASSERTION_ID__');
        $this->replaceAttributes($xpath, 'InResponseTo', '__IN_RESPONSE_TO__');
        $this->replaceAttributes($xpath, 'SessionIndex', '__SESSION_INDEX__');
        foreach (self::TIME_ATTRIBUTES as $attribute) {
            $this->replaceAttributes($xpath, $attribute, '__TIME__');
        }
        $this->replaceText($xpath, '//ds:DigestValue', '__DIGEST__');
        $this->replaceText($xpath, '//ds:SignatureValue', '__SIGNATURE__');

        return $document->saveXML();
    }

    /**
     * Replace the ID of the matched elements, and the ds:Reference URIs that refer to them.
     */
    private function replaceIds(DOMXPath $xpath, string $query, string $placeholder): void
    {
        foreach ($xpath->query($query) as $element) {
            /** @var DOMElement $element */
            $id = $element->getAttribute('ID');
            if ($id === '') {
                continue;
            }
            $element->setAttribute('ID', $placeholder);
            foreach ($xpath->query('//ds:Reference[@URI=' . $this->literal('#' . $id) . ']') as $reference) {
                $reference->setAttribute('URI', '#' . $placeholder);
            }
        }
    }

    private function replaceAttributes(DOMXPath $xpath, string $attribute, string $placeholder): void
    {
        foreach ($xpath->query(sprintf('//@%s', $attribute)) as $attr) {
            $attr->value = $placeholder;
        }
    }

    private function replaceText(DOMXPath $xpath, string $query, string $placeholder): void
    {
        foreach ($xpath->query($query) as $element) {
            $element->textContent = $placeholder;
        }
    }

    private function literal(string $value): string
    {
        return "'" . str_replace("'", '', $value) . "'";
    }
}
