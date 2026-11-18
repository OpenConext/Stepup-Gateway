<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: sfo-redirect-joe-a-sms-loa2
 * Generated: 2026-11-04T16:03:44+00:00
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
 *   - SAMLRequest: Decoded (1056 bytes XML)
 *   - Issuer: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/second-sp
 *   - Destination: https://gateway.dev.openconext.local/second-factor-only/single-sign-on
 *   - ID: _75e1428b3afb413c955d08e231676b11409d2b2084
 *   - IssueInstant: 2026-11-04T16:01:33Z
 *   - NameID: urn:collab:person:institution-a.example.com:joe-a-sms
 *   - AuthnContextClassRef: http://dev.openconext.local/assurance/sfo-level2
 *   - AssertionConsumerServiceURL: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/second-sp
 *   - RelayState: https://ssp.dev.openconext.local/simplesaml/sp.php?sp=second-sp
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_75e1428b3afb413c955d08e231676b11409d2b2084" Version="2.0" IssueInstant="2026-11-04T16:01:33Z" Destination="https://gateway.dev.openconext.local/second-factor-only/single-sign-on" AssertionConsumerServiceURL="https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/second-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/second-sp</saml:Issuer>
  <saml:Subject>
    <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:institution-a.example.com:joe-a-sms</saml:NameID>
  </saml:Subject>
  <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://dev.openconext.local/assurance/sfo-level2</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=pZRLjxoxDMe%2FCso98%2BKxbARIFFQVadsioD30UmUyBrLKJGmcYeHbN%2FNYFdRle%2BhpZDuOf%2F7bmQnyUlk2r%2FxRb%2BBXBeh751JpZE1gSiqnmeEokWleAjIv2Hb%2B%2BYllUcKsM94Io8hVyvsZHBGcl0aT3mo5JT8fhpAOsnHe5%2Ft8kPbF43BYJGPI%2BunoYZSn6SB5LLI8S8YD0vsODkPmlISLQjpiBSuNnmsfXEk2omlKk8EuHbEkZf3%2BD9Jbhm6k5r7JOnpvkcXxgXt44ZeogFNkLGhhNJx9pIzgKkYIZkH3XHjjqNHqEqPUBwUU5UHTmnv%2B2sLCaKxKcFtwJyng2%2BbpTxFEe6eALK2CWqi4NEWlILJHGzc2tt%2BMcoGtt4VBS3rrTukPUheB532R8%2FYQsk%2B73Zquv253ZDapr2aNaG72n5QleF5wz28hJ%2FF1ibbetsqfQfjO%2BhJIV8veR%2BNK7u%2B3kEZp45FhDs1RVmm0IOReQkFmdVpQQvGc2bARRjMZtkD6qp4J5RGceQ0fCVOyZwOUUyyxg2sJZp11Q2e74NooKS7%2FhKx1voX0jmuUoH1YEaXMy8JB2LQp8a4CEr8W6d4YFM2LCyvkg%2BqdPteuhQpPZQP7ZlZhVG%2BOKRypQlUBMe4NVXAClXW9vXlXG7sLEf%2F9L5j9Bg%3D%3D&RelayState=https%3A%2F%2Fssp.dev.openconext.local%2Fsimplesaml%2Fsp.php%3Fsp%3Dsecond-sp&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=Dc3241gNJ3OE3X3Ulj8FVluNd7S7L2RXRsTh7kKLfZdFg77nxFPeFCZWzgLOWhD%2FGj2%2Fqx6%2FQYDQcmLeRAz2bsNSZ2QkrcUDpSHmUltCOYwAHYgH6c6S8HC0pEfXhVLwQpNkqDyoHKa3LOG9scgRTaCtFo%2BPaQq8e8r83Iz6j6K%2FUeXiko8TzOTSAD0iXRg2NQzIJV9oCudQu1xc5xPGnA%2Fdi7XoxhL1XeOBcCRpg3DE2H%2Biz3AD3dvhUtQiycJ5E6sJWj1WGVQNwl%2BTmUls8YApIzo%2B0mmQBxFSdHi68dHz89jQNKxeU44f4cjuhYU0dNDSevJIsZs%2FIy5IpjFrZEcpGHcaH878nM7x%2BW14YzLVwnrgorpscFZLfvcPIok9yUjQbw6d%2BBiumuAac9LO7xFGocWn14%2BqicMVLMmRFX0zsmkUYblX0DUc3xdB7CYaJ%2BIjMNwIi%2FU20SVYENr1KddyoXxVbI5mtvcRxkh0lqvzKfR3ILBGQgzGc%2BmdT8ez';
