<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: sfo-redirect-joe-a-yk-loa2
 * Generated: 2026-10-27T09:01:20+00:00
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
 *   - SAMLRequest: Decoded (1055 bytes XML)
 *   - Issuer: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/second-sp
 *   - Destination: https://gateway.dev.openconext.local/second-factor-only/single-sign-on
 *   - ID: _543a3d478a32dbfd989f3348e68e7b69e646a52f8f
 *   - IssueInstant: 2026-10-27T08:28:34Z
 *   - NameID: urn:collab:person:institution-a.example.com:joe-a-yk
 *   - AuthnContextClassRef: http://dev.openconext.local/assurance/sfo-level2
 *   - AssertionConsumerServiceURL: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/second-sp
 *   - RelayState: https://ssp.dev.openconext.local/simplesaml/sp.php?sp=second-sp
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_543a3d478a32dbfd989f3348e68e7b69e646a52f8f" Version="2.0" IssueInstant="2026-10-27T08:28:34Z" Destination="https://gateway.dev.openconext.local/second-factor-only/single-sign-on" AssertionConsumerServiceURL="https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/second-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/second-sp</saml:Issuer>
  <saml:Subject>
    <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:institution-a.example.com:joe-a-yk</saml:NameID>
  </saml:Subject>
  <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://dev.openconext.local/assurance/sfo-level2</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=pZRNj9owEIb%2FSuR7EkhYCBYgUVBVpG2LgPbQS2WcCXjr2K7HYeHfr%2FOxKqjL9tBTNDMezzPvjDNBVkpD55U7qg38rgBdcC6lQtoEpqSyimqGAqliJSB1nG7nnx9pEvWosdppriW5Snk%2FgyGCdUIrEqyWU%2FLzYZCyNB%2BMMpYm%2Bb7Ix9m4SNNBBsMMRvvhGIaDIXtIiqwgwXew6DOnxF%2Fk0xErWCl0TDnv6iXDsN8Lk9Gul9Eko%2BngBwmWvhuhmGuyjs4ZpHF8YA6e2SXK4RRpA4prBWcXSc2ZjBG8mYcF407bUCt5iVGog4QQxUGFNff8tYWFVliVYLdgT4LDt83jnyKI5k4BURoJtVBxqfNKQmSOJm5sbL9JyDi23hYGDQnWndIfhMo9z%2Fsi79tDSD%2Ftdutw%2FXW7I7NJfTVtRLOz%2F6QswbGcOXYLOYmvS7T1ttX%2BCbjrrC%2BedLUMPmpbMne%2FhX7UbzzCz6E5SiuFBrgoBORkVqd5JSTbU%2BM3Qisq%2FBYIV9UzCVkEZ1bDR1yX9ElDyMLLr46tBZh11g2c6YJrLQW%2F%2FJOxlvmW0VmmUIByfkOk1M8LC37RpsTZCkj8WqR7YpA3D85vkPOid%2FJcuxbSv5QNFM2o%2FKTenJI%2FUvmqHGIsdCjhBDLpenvzrjZ2FyL%2B%2B1cwewE%3D&RelayState=https%3A%2F%2Fssp.dev.openconext.local%2Fsimplesaml%2Fsp.php%3Fsp%3Dsecond-sp&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=Uq4GqpoqyBFcbHZzmA3LlDgI9AJPJrQ3P5oMrBsA%2BhANsTMOGWqhsAzKaKoWch2ZDIgQcBWIkGJlgadPPv13FBxHQOCFp6qQo%2F8WALthajO38gfp0T8X5JOqd6gVC1YKKR3hOGLEU%2BoazeyH8S%2Bmb078UulPqSXPzJfWHZUYWBdBXIwxEGz7eGXBRPFWPRK7RUfodNt9%2FXYFGJHXu096LrCOTJ%2FMtAc5BkYcMBCCDJesZ%2BMa0Ex%2BrhMs0in%2FMkdLAxVZ%2FwkNdfJzOEEHKnw%2FD%2FBJ5jCveG3bNLFUhcYoLVheME1%2F5dIlxMAgxSTR1PVjXqmdaZNvh75y%2F9%2BhEFjF%2B1ftJJsVq%2FNWyRU6lwo00O3NXZDk7nPx0Fmt%2Flw%2FqAyma%2FJgTj1tz4ywVzkkBa1WXTFwO5SepJvFM45bwdgcymVZsjio5Bs7iFTvvkQfdCR8GFVrgQGK1gmvC2BDI9Vaewle629vN%2FQ2H7zOrZZ8sr35izOVovNB7PwEwUiprTy1';
