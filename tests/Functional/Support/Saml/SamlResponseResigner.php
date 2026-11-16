<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support\Saml;

use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RuntimeException;

/**
 * Makes a captured (pre-signed) SAML Response valid for a new AuthnRequest.
 *
 * Only the following is changed, all other XML is left as-is:
 *  - InResponseTo (Response and SubjectConfirmationData) is set to the ID of the AuthnRequest
 *  - All timestamps are shifted by the same amount, so the Response was "issued" at the current time
 *    (the relative validity windows are preserved)
 *  - The Assertion signature is recreated. The private key is found by looking up the certificate in
 *    the Response's ds:KeyInfo in the devconf keys fixtures (Fixtures/devconf/keys/*.crt).
 *
 * Only responses with a signed Assertion are supported.
 */
class SamlResponseResigner
{
    private const NS_SAMLP = 'urn:oasis:names:tc:SAML:2.0:protocol';
    private const NS_SAML = 'urn:oasis:names:tc:SAML:2.0:assertion';

    private string $keysPath;

    public function __construct(string $fixturesPath)
    {
        $this->keysPath = $fixturesPath . '/devconf/keys';
    }

    /**
     * @param string $base64Response Base64 encoded SAML Response (as in the SAMLResponse POST parameter)
     * @param SamlRequestParser $request The AuthnRequest the response must answer
     * @param DateTimeImmutable|null $now The time the response is made valid for, defaults to the current time
     *
     * @return string The base64 encoded and re-signed response
     */
    public function resign(
        string $base64Response,
        SamlRequestParser $request,
        ?DateTimeImmutable $now = null
    ): string {
        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $document = new DOMDocument();
        $document->preserveWhiteSpace = true;
        $document->loadXML(base64_decode($base64Response, true));

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('samlp', self::NS_SAMLP);
        $xpath->registerNamespace('saml', self::NS_SAML);
        $xpath->registerNamespace('ds', XMLSecurityDSig::XMLDSIGNS);

        $response = $document->documentElement;
        $assertion = $this->getElement($xpath, '/samlp:Response/saml:Assertion');
        $signature = $this->getElement($xpath, './ds:Signature', $assertion);
        $certificate = $this->getElement($xpath, './/ds:X509Certificate', $signature)->textContent;
        $privateKeyFile = $this->findPrivateKeyFile($certificate);

        $this->updateInResponseTo($xpath, $response, $request->getId());
        $this->shiftTimes($xpath, $response, $now);

        $assertion->removeChild($signature);
        $this->sign($assertion, $privateKeyFile, $certificate);

        return base64_encode($document->saveXML());
    }

    private function updateInResponseTo(DOMXPath $xpath, DOMElement $response, string $requestId): void
    {
        $response->setAttribute('InResponseTo', $requestId);
        foreach ($xpath->query('//saml:SubjectConfirmationData[@InResponseTo]') as $node) {
            $node->setAttribute('InResponseTo', $requestId);
        }
    }

    /**
     * Shift all timestamps by the difference between the Response IssueInstant and $now.
     */
    private function shiftTimes(DOMXPath $xpath, DOMElement $response, DateTimeImmutable $now): void
    {
        $timeAttributes = ['IssueInstant', 'NotBefore', 'NotOnOrAfter', 'AuthnInstant', 'SessionNotOnOrAfter'];
        $shift = $now->getTimestamp() - $this->parseTime($response->getAttribute('IssueInstant'));

        foreach ($timeAttributes as $attribute) {
            foreach ($xpath->query(sprintf('//@%s', $attribute)) as $attr) {
                $time = $this->parseTime($attr->value) + $shift;
                $attr->value = gmdate('Y-m-d\TH:i:s\Z', $time);
            }
        }
    }

    private function parseTime(string $time): int
    {
        return (new DateTimeImmutable($time))->getTimestamp();
    }

    private function sign(DOMElement $assertion, string $privateKeyFile, string $certificate): void
    {
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKeyFile, true);

        $dsig = new XMLSecurityDSig();
        $dsig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $dsig->addReference(
            $assertion,
            XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N],
            ['id_name' => 'ID', 'overwrite' => false]
        );
        $dsig->sign($key);
        $dsig->add509Cert($certificate, false);

        // The schema demands the signature directly after the Issuer
        $issuer = null;
        foreach ($assertion->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'Issuer') {
                $issuer = $child;
                break;
            }
        }
        $dsig->insertSignature($assertion, $issuer?->nextSibling);
    }

    /**
     * Find the private key matching the certificate by looking through the devconf key fixtures.
     */
    private function findPrivateKeyFile(string $certificate): string
    {
        $needle = $this->normalizeCertificate($certificate);

        foreach (glob($this->keysPath . '/*.crt') as $certFile) {
            if ($this->normalizeCertificate(file_get_contents($certFile)) !== $needle) {
                continue;
            }
            $keyFile = substr($certFile, 0, -4) . '.key';
            if (!is_file($keyFile)) {
                throw new RuntimeException(sprintf('Found certificate %s but no private key %s', $certFile, $keyFile));
            }

            return $keyFile;
        }

        throw new RuntimeException(sprintf('No matching certificate found in %s', $this->keysPath));
    }

    private function normalizeCertificate(string $certificate): string
    {
        return preg_replace(
            '/-----(BEGIN|END) CERTIFICATE-----|\s+/',
            '',
            $certificate
        );
    }

    private function getElement(DOMXPath $xpath, string $query, ?\DOMNode $context = null): DOMElement
    {
        $nodes = $context ? $xpath->query($query, $context) : $xpath->query($query);
        if ($nodes->length !== 1) {
            throw new RuntimeException(sprintf('Expected exactly one node for "%s", found %d', $query, $nodes->length));
        }

        return $nodes->item(0);
    }
}
