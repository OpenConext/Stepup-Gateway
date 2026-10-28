<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support\Saml;

use DOMDocument;
use DOMElement;
use DOMNode;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RuntimeException;
use Throwable;

/**
 * Verifies the XML signatures in a SAML message against a known certificate.
 *
 * The certificate is never taken from the message itself (ds:KeyInfo), so a message signed with any other key fails.
 * Every ds:Signature in the message is verified. Besides the cryptographic check, each signature must:
 *  - be an enveloped signature over exactly one element: its parent (guards against signature wrapping)
 *  - use RSA-SHA256 and a SHA-256 digest
 */
class SamlSignatureVerifier
{
    private const NS_SAMLP = 'urn:oasis:names:tc:SAML:2.0:protocol';
    private const NS_SAML = 'urn:oasis:names:tc:SAML:2.0:assertion';

    /**
     * @return string[] The local names of the signed elements (e.g. ['Response', 'Assertion'])
     *
     * @throws RuntimeException When there is no signature, or any of the signatures is not valid
     */
    public function verify(string $xml, string $certificateFile): array
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = true;
        if (!$document->loadXML($xml)) {
            throw new RuntimeException('The SAML message is not valid XML');
        }

        $signatureCount = $document->getElementsByTagNameNS(XMLSecurityDSig::XMLDSIGNS, 'Signature')->length;
        if ($signatureCount === 0) {
            throw new RuntimeException('The SAML message does not contain a signature');
        }

        $signed = [];
        for ($position = 0; $position < $signatureCount; $position++) {
            $signed[] = $this->verifySignature($document, $position, $certificateFile);
        }

        return $signed;
    }

    private function verifySignature(DOMDocument $document, int $position, string $certificateFile): string
    {
        // XMLSecurityDSig modifies the document it validates, so every signature gets a fresh copy.
        $copy = new DOMDocument();
        $copy->preserveWhiteSpace = true;
        $copy->loadXML($document->saveXML());

        try {
            $dsig = new XMLSecurityDSig();
            // SAML identifies elements with ID, xmlseclibs only looks for Id by default
            $dsig->idKeys = ['ID'];
            $signatureNode = $dsig->locateSignature($copy, $position);
            if ($signatureNode === null) {
                throw new RuntimeException('Signature not found');
            }
            $dsig->canonicalizeSignedInfo();

            // validateReference() detaches the signature from the document, so remember where it was.
            $parent = $signatureNode->parentNode;

            // Throws when the digest of a referenced element does not match.
            $dsig->validateReference();

            $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
            $key->loadKey($certificateFile, true, true);
            if ($dsig->verify($key) !== 1) {
                throw new RuntimeException('The signature value does not match the certificate');
            }
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf('Signature #%d is invalid: %s', $position + 1, $e->getMessage()), 0, $e);
        }

        return $this->assertSignsParent($dsig, $signatureNode, $parent, $position);
    }

    private function assertSignsParent(
        XMLSecurityDSig $dsig,
        DOMElement $signatureNode,
        ?DOMNode $parent,
        int $position
    ): string {
        $validated = array_values($dsig->getValidatedNodes() ?? []);

        if (count($validated) !== 1 || !$validated[0]->isSameNode($parent)) {
            throw new RuntimeException(sprintf(
                'Signature #%d does not sign exactly its parent element',
                $position + 1
            ));
        }

        $signedNamespaces = [self::NS_SAMLP, self::NS_SAML];
        if (!$parent instanceof DOMElement || !in_array($parent->namespaceURI, $signedNamespaces, true)) {
            throw new RuntimeException(sprintf('Signature #%d is not placed in a SAML element', $position + 1));
        }

        $algorithms = $signatureNode->ownerDocument->saveXML($signatureNode);
        foreach (['http://www.w3.org/2001/04/xmldsig-more#rsa-sha256', 'http://www.w3.org/2001/04/xmlenc#sha256'] as $algorithm) {
            if (!str_contains($algorithms, $algorithm)) {
                throw new RuntimeException(sprintf('Signature #%d does not use %s', $position + 1, $algorithm));
            }
        }

        return $parent->localName;
    }
}
