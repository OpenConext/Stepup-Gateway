<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: sso-redirect-third-sp-loa2-1
 * Generated: 2026-11-05T19:08:07+00:00
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
 *   - SAMLRequest: Decoded (872 bytes XML)
 *   - Issuer: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/third-sp
 *   - Destination: https://gateway.dev.openconext.local/authentication/single-sign-on
 *   - ID: _89849df3b9f9fa171ad2e207bdbd41151f5c12bf0d
 *   - IssueInstant: 2026-11-05T19:07:26Z
 *   - AuthnContextClassRef: http://dev.openconext.local/assurance/loa2
 *   - AssertionConsumerServiceURL: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/third-sp
 *   - RelayState: https://ssp.dev.openconext.local/simplesaml/sp.php?sp=third-sp
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_89849df3b9f9fa171ad2e207bdbd41151f5c12bf0d" Version="2.0" IssueInstant="2026-11-05T19:07:26Z" Destination="https://gateway.dev.openconext.local/authentication/single-sign-on" AssertionConsumerServiceURL="https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/third-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/third-sp</saml:Issuer>
  <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://dev.openconext.local/assurance/loa2</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=pVNLj9owEP4rke%2BJk3RZNhYgUVBVpG2LgPbQS%2BXYE7Dk2Klnso9%2FXxNYlaqUS0%2BRx%2F7me8xkgrK1nZj3dHAb%2BNkDUvLSWodiuJiyPjjhJRoUTraAgpTYzj89ijLLRRc8eeUtu4DcRkhECGS8Y8lqOWU%2FHqqHu0o37%2BqqqRpZjAupSyjzca1rfVcUo6IZqaKsm1yz5BsEjMgpi40iHLGHlUOSjmIpL%2B%2FTokjz0a6oRD4W5f13liyjG%2BMkDagDUYeC870keJavmYanzHfglHfwQpn1SlouYw7gyKgBxNG4vYUUzd6lR83zN%2FkL77BvIWwhPBkFXzePvwkQu%2BvN0bSdhWNIvPW6t5B1h44PZzx9y1QqHKp0MEGn2LFkfQ75vXE6yrmdb316hOLjbrdO11%2B2OzabHDuLIa8w%2B0%2BRLZDUkuQfGif8kmFy2qjPUdtqufbWqNfkgw%2BtpNvSjxWj02Z4KihIhyaOIoZurX9eBIhzmzIKPTD%2BRnLeWNDD%2FsahUDRyNnxZWti4eBtoBvvR%2FfXZR%2F2RVQG3XpZnU1e7nO7%2BSc%2F%2F%2FqdmvwA%3D&RelayState=https%3A%2F%2Fssp.dev.openconext.local%2Fsimplesaml%2Fsp.php%3Fsp%3Dthird-sp&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=GGHNybw0zhYLNt3WYQR1B6wcGGrlPOv5DgiHplxxaReexBMdJOQTt8vErI29EXp5ZHeEcYFIwKwECI0iJFaqo4XpxCPlSa8%2Bm76u1icJldgB7ndXCZwsoDZDzedUcDQYAYczjzD9HoPSdmcZ%2BwhDtOBUr%2FheYs01b0RqA2wlXRxYgmcd%2B4Gfr3n8xVCgEnALeEyGRD1zLNn8g6iCUZevO2alR056r3xwhwD0C4HxOjErGLrNhXhcT05PypIsEG3g7wlwM2batu4Qg6SFzJbA%2BCGmdZgIM1Or883kbNcHpsVJoQWuQuRSxi9mdMBCgqC2ndHJMcK1CffD3Pu7M2n9xGy%2Bm2sEKKRdJiaF2jxaTOoUdhApC6Gn5Z8VWn2Hd4zEydF2WqflM%2BiFC0%2Bk6jyYx6LF3LrojHBG8CG4Z0S%2BE6vUzZrzNhDugV1NgyK%2BCrhJjH%2BcK96jJ4gqtm8GYZ8IglyXNd%2B7cQa9gNu0xlZc5o5D4LsTKNnGEy%2FcwqfRatZO';
