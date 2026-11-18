<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: sso-redirect-loa3-1
 * Generated: 2026-11-05T19:05:53+00:00
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
 *   - ID: _c79cc8a40c24ea03691737ddc6386f4c66320e70a1
 *   - IssueInstant: 2026-11-05T19:04:59Z
 *   - AuthnContextClassRef: http://dev.openconext.local/assurance/loa3
 *   - AssertionConsumerServiceURL: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp
 *   - RelayState: https://ssp.dev.openconext.local/simplesaml/sp.php?sp=default-sp
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_c79cc8a40c24ea03691737ddc6386f4c66320e70a1" Version="2.0" IssueInstant="2026-11-05T19:04:59Z" Destination="https://gateway.dev.openconext.local/authentication/single-sign-on" AssertionConsumerServiceURL="https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer>
  <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://dev.openconext.local/assurance/loa3</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=pVNLj9owEP4rke95s2GxAImCqiJtWwS0h71UU2cAS46deib7%2BPc1gVVRy3LpKfLY33yPmYwJGtPKWccHu8ZfHRJHL42xJPuLiei8lQ5Ik7TQIElWcjP7%2FCCLJJOtd%2ByUM%2BICchsBROhZOyui5WIifqjhSKl7GGSqGCBkZTXKh%2BWwrlVV3le7gaqqsshwmEEuou%2FoKSAnIjQKcKIOl5YYLIdSVlRxnsfZ3TYfyWwg70aPIloEN9oC96gDc0syTffA%2BAyvSY1PiWvRKmfxhRPjFJgUQg5oWaselJK2e4Mx6b2Nj5pnb%2FLnzlLXoN%2Bgf9IKv60f%2FhAQtdebk25ag8eQ0sbVncGkPbRpf6bTt4hBUV%2BtcQed4ZhaEa3OMX%2FQtg6Cbif88%2FSI5KftdhWvvm62Yjo%2B9pZ9Yn76nzIbZKiB4S%2BV4%2FSSY3zaqi9B3XKxckar1%2Bij8w3wbfHHiq7jXf9UsgdLOowjBG%2BMe557DLObCPYdivSN5Ly1WPc7HAbDwcrZ8mVpbsLyrXHXBxD8X59%2F0B9YFabGQXk2dbXL6e5d%2BvTf%2F2r6Gw%3D%3D&RelayState=https%3A%2F%2Fssp.dev.openconext.local%2Fsimplesaml%2Fsp.php%3Fsp%3Ddefault-sp&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=aeNePeZWHMgdAXKXlt9Qn6yYPq0kr6wZNnHsL5U3GpopHZVLS7viPmfNNpAE6JUey1RV%2FYo0Vb8nXzwAz1eSB%2Fw1FUQamqiCIdj%2BdrgPD1VcRIxcIfflo4nv6m8%2FcbAu%2Fk3IxSxP%2B%2Bjv4L5WrBNdotocnpLCy2x8wHSzAMjEW11Jq66691NIAFK8yViVmR8qxGuVddbfu43J1wBHdhcBb%2F%2FGXOVH15kpuGLcaKIkZbAuMPix%2BZFMfb%2F7exCqqELadQtgzZcueyS7iPpXExdXeY3aKtauoSfDUXt3AE8aeyB4mLJuZNfzGcoJ2odtuEJQ4AmF2wtr8o7yG1BEHhIukxtrMFSRKftn75uJGLnOfQaiwOSi%2FikJGlI3syt54ksclLPRKk0EgiKqdUdcnYq2iFdIlQ9dhNTG%2BKMUP2dQ46AoBV6Dey74ZspO1GqEBdGqx%2Bo56%2Fxblhk3LkomZ64zirPTPGV8wzDjulBa4h6yEK6gRWljAxgX8tN66AfVLvtc';
