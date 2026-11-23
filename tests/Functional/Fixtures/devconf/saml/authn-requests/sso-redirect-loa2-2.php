<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: sso-redirect-loa2-2
 * Generated: 2026-11-04T08:47:50+00:00
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
 *   - ID: _acaf4818072717a3edc93c796f80f5b8210a27e196
 *   - IssueInstant: 2026-11-04T08:45:20Z
 *   - AuthnContextClassRef: http://dev.openconext.local/assurance/loa2
 *   - AssertionConsumerServiceURL: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp
 *   - RelayState: https://ssp.dev.openconext.local/simplesaml/sp.php?sp=default-sp
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_acaf4818072717a3edc93c796f80f5b8210a27e196" Version="2.0" IssueInstant="2026-11-04T08:45:20Z" Destination="https://gateway.dev.openconext.local/authentication/single-sign-on" AssertionConsumerServiceURL="https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer>
  <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://dev.openconext.local/assurance/loa2</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=pVNLj9owEP4rke8hj2VJsACJgqoibVsEtIdeqllnApYcO%2FVM9vHvawKrRS3l0lPksb%2F5HjOZEDSmlfOOD3aDvzokjl4aY0n2F1PReSsdkCZpoUGSrOR2%2FvlB5oNUtt6xU86IC8htBBChZ%2B2siFbLqfgJCuphmZVpkRdZAXdYqfGdKsajukzr%2B8cyz1LIC8zGIxF9R08BORWhUYATdbiyxGA5lNJ8FGdZnA53aSmH9zJPf4hoGdxoC9yjDswtySTZA%2BMzvA4qfBq4Fq1yFl94YJwCk0DIAS1r1YMS0nZvMCa9t%2FFR8%2FxN%2FsJZ6hr0W%2FRPWuG3zcM7AVF7vTnppjV4DClpXNUZHLSHNunPdPrmMSjqqxXW0BmOqRXR%2BhzzB22rIOh2wo%2BnRyQ%2F7XbreP11uxOzybG37BPzs%2F%2BU2SBDBQx%2FqJwklxyT01Z9CepWy7UzWr1GH51vgG%2BLP1Z0Fdf9U8keLOkwjhC8Me554THMbirYdyiSN5Lz1mLV73AYDAcrZ8uXpYUJy7fBug8g%2BL8%2B%2F6A%2FsCpMjIP8bOpql9PdP%2BmTv%2F%2Br2W8%3D&RelayState=https%3A%2F%2Fssp.dev.openconext.local%2Fsimplesaml%2Fsp.php%3Fsp%3Ddefault-sp&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=phk6NUO%2FOi%2FTwS0TP3FhDzGUSm7H6v5g6HXE27SkbBGyztYdx2SlfI2DWP0kWEvXDmmRtXZxHpCmtQMiYnBNpCCBOioWV3CpsdbUKFxccVNteVgnRQx6hA8%2Be1E1G%2FtQCgM%2BdKYAHqCebHI1Szak1EcnljWpagaZbc8N00C%2BxESYPZa5mBVydvcSkf3EDHRmSzrr5t3weB%2F%2Fv0BjEybW0g3mtEG%2F5iloGFl0UYx2kw%2FFTwNi2Xf173sYfp%2B1WYtDdV8MEt4xNhfheBoxovMohaYImk4QLWSogyAUiiMD6mEtY4mfnIGv43yCXujwpq2RJu2Yq2IGxqeoQ7CLSWJsPa%2Fi9TdZYbUhHbVHZgTWwOYqQ9lV45c0k%2FTD2kA8BWQdNaNUMS7LGXsD2Yn1WzyNjFUxTY%2FDTBtH%2F1k2Z4y6D4p%2FbWg5sqUT%2FRxrsT7wCnM9tWKotfgX9n%2FwjpdRnLZuiDntJe%2BzCmGZAebtGW6zaM5uHARfJlfCUZVZalwCxXsF';
