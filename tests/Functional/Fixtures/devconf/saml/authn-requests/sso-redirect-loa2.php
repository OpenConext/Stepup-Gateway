<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: sso-redirect-loa2
 * Generated: 2026-10-27T10:49:07+00:00
 *
 * This is an HTTP-Redirect AuthnRequest (query string format).
 * Captured from: browser address bar or SAML Tracer
 * Format: SAMLRequest=...&SigAlg=...&Signature=...
 *
 * Message Summary:
 *   - Binding: HTTP-Redirect (query string)
 *   - Parameters: SAMLRequest, RelayState, SigAlg, Signature
 *   - Signed: Yes
 *   - Signature Algorithm: http://www.w3.org/2001/04/xmldsig-more#rsa-sha256
 *   - SAMLRequest: Decoded (876 bytes XML)
 *   - Issuer: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/default-sp
 *   - Destination: https://gateway.dev.openconext.local/authentication/single-sign-on
 *   - ID: _59a8584f503b20f07ce4ae7068ce814edc5560b8f3
 *   - IssueInstant: 2026-10-27T10:47:41Z
 *   - AuthnContextClassRef: http://dev.openconext.local/assurance/loa2
 *   - AssertionConsumerServiceURL: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp
 *   - RelayState: https://ssp.dev.openconext.local/simplesaml/sp.php?sp=default-sp
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_59a8584f503b20f07ce4ae7068ce814edc5560b8f3" Version="2.0" IssueInstant="2026-10-27T10:47:41Z" Destination="https://gateway.dev.openconext.local/authentication/single-sign-on" AssertionConsumerServiceURL="https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer>
  <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://dev.openconext.local/assurance/loa2</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=pVPLrtowEP2VyPuQkJtAagESBVVFum0R0C66qQZnApYcO%2FVM7uPvawJXRS1l01Xksc%2Bcx0wmBI1p5bzjo93gzw6Jo5fGWJL9xVR03koHpElaaJAkK7mdf3qU2SCVrXfslDPiCnIfAUToWTsrotVyKn4U76Asyrwu0od9ltbpWGEOOE5HpcJymGOlimKU7sv6QUTf0FNATkVoFOBEHa4sMVgOpTQbxcM0zsa7YSrzscyH30W0DG60Be5RR%2BaWZJIcgPEZXgcVPg1ci1Y5iy88ME6BSSDkgJa16kEJaXswGJM%2B2Pikef4mf%2BEsdQ36LfonrfDr5vE3AVF7uznppjV4CilpXNUZHLTHNunPdP5mMSjqqxXW0BmOqRXR%2BhLze22rIOh%2BwvvzI5Ifd7t1vP6y3YnZ5NRb9on52X%2FKbJChAoY%2FVE6Sa47Jeas%2BB3Wr5doZrV6jD843wPfFnyq6iuv%2BqWQPlnQYRwjeGPe88BhmNxXsOxTJG8lla7HqdzgMhoOVi%2BXr0sKE5dtg3QcQ%2FN%2Bef9AfWBUmxkF2MXWzy%2Fnun%2FTJ3%2F%2FV7Bc%3D&RelayState=https%3A%2F%2Fssp.dev.openconext.local%2Fsimplesaml%2Fsp.php%3Fsp%3Ddefault-sp&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=HGc5kqiiceX3KVexMkV9WmLAqhlh4Nq1TlD7Qfna8ULcMvvvAGxEI6NDxemIWlj9jiwOTIGaH%2FzmOK01TrODaHpyhDurjfPcALTYIzSSnusZhPIhH%2BC0dXfGR06%2B3vqKNHT%2B79p3fU5xEaky74MpY92W1UXVfBLt4k74tIZrsY1zd5zp1jVkENBzQq%2Fi7R8yXH3lv5R%2F2kDUySg0PS8%2FTyiOIQs%2FrOkpqj2Nxullni4e9HUZjdexNZYtYGJd1tSOyQrb8r0D25cg3qPRJCW3Zw2IpYsO%2BVcNNVjM2FUKJByXBH2inUxlQST0nMX2ijWJG%2FbVAqbcJrZJEZ04DAf5ZDXCYn%2F3jSvJumyQ0BBKezc9N2ZJCecLl1m2tJsIlqYD2mK8wR%2Bbjx2rM5GZhRM%2FPsLcnko1%2Bg%2B9o%2BYyqI4Fbeci%2BCOI%2F8M%2F87e0EQxILIb%2BCBdDw8yxWshBmLlhL%2B8xlvNKjOiwnnSrji6AS5fumuJlWsQLfGHC8%2FW%2B%2Fe9q26PZ';
