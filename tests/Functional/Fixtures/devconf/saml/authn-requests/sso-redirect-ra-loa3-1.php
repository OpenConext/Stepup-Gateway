<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: sso-redirect-ra-loa3-1
 * Generated: 2026-11-05T19:10:24+00:00
 *
 * This is an HTTP-Redirect AuthnRequest (query string format).
 * Captured from: browser address bar or SAML Tracer
 * Format: SAMLRequest=...&SigAlg=...&Signature=...
 *
 * Message Summary:
 *   - Binding: HTTP-Redirect (query string)
 *   - Parameters: SAMLRequest, SigAlg, Signature
 *   - Signed: Yes
 *   - Signature Algorithm: http://www.w3.org/2001/04/xmldsig-more#rsa-sha256
 *   - SAMLRequest: Decoded (779 bytes XML)
 *   - Issuer: https://ra.dev.openconext.local/authentication/metadata
 *   - Destination: https://gateway.dev.openconext.local/authentication/single-sign-on
 *   - ID: _f263e07a854f21d4b13f57af0ece236345225650b2962e1b509fba2f1ac5
 *   - IssueInstant: 2026-11-05T19:09:38Z
 *   - AuthnContextClassRef: http://dev.openconext.local/assurance/loa3
 *   - AssertionConsumerServiceURL: https://ra.dev.openconext.local/authentication/consume-assertion
 */

/*
Decoded XML:

<?xml version="1.0" encoding="UTF-8"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_f263e07a854f21d4b13f57af0ece236345225650b2962e1b509fba2f1ac5" Version="2.0" IssueInstant="2026-11-05T19:09:38Z" Destination="https://gateway.dev.openconext.local/authentication/single-sign-on" AssertionConsumerServiceURL="https://ra.dev.openconext.local/authentication/consume-assertion" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://ra.dev.openconext.local/authentication/metadata</saml:Issuer>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://dev.openconext.local/assurance/loa3</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=lVJBbtswELznFQLvkijKUmzCduAmKGogRQ1byaGXYkWtHAES6XIpN%2F19aVlBDDQN0iuXM7MzO%2FOb564NjmipMXrBkoizALUyVaP3C%2FZQfA6n7GZ5NSfo2oNc9e5Jb%2FFnj%2BQCD9Qkh8GC9VZLA9SQ1NAhSafkbvX1XoqIy4M1zijTsgvI%2BwggQuv8RixY3y3Yj1rkKfJrmGaTWiTVpEzSOruGmqNCkebpJBMiyzNeilkuMCkzPqtLEHUCKmPB44s7cXK3JupxrcmBdv6JizxMkpBnRTKTfCbT6XcW3Hl%2FjQY3oJ6cO5CM4z04%2FAW%2FowqPkTmcQtL47KLWKGhj8Mmgdo0aQDH5%2BFoMqdnr8ORi9WLo1mjqO7Q7tMdG4cP2%2FlXAwoe41ZkivAhpMyb8qdHnw70Xbnn%2BRPJLUWzCzbddwZbDfeUQjV3%2B5z4dOqjAwTy%2BJBkbM5YFq6E63r3zPKPc5dNt6%2B1ssR7Evfbbwp7aglYYtwbSUe9NlvPsn%2FLx33VeXv0B&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=Mv4%2FyVZlnZf4%2BUhHXWH3q8wNr7Zi0xeSSDcJKXQwH7KVOaFY%2FvutnDvr9y%2Bc7mU7u%2BWtKXGbFuL3IP1M4NfehI5qkFF5RrY2o4oPBM3wzx6Gcx0bP%2FKJa%2Frk4BblndMJOFZmupUf9W2%2FgM%2B2mbkpUoeMHHLOX5mSGT%2BLCcHjd7w6CJxKPQG5TtDV0L10dVv%2BytL%2FXjfEQMwqC9rvCsAKR98RsjGrXB35Fz8BW4S3jYc5i37%2B%2BNCMUT4YPredqMlJPMqBCJiMVLQ8PHj6FH0bg8pta8iXdWKeBPzKPPT5K1GpzKlYw%2BFJiRIAadgd%2F5n31nQLRVYaJjoxW%2BQKVeFWB2XYtqdvxfFC7AZV5s89bPPKwdi9Ys%2BXHM1hSB0jtTzFDoeg6OHIGwISF6PrF%2B7sI5QOgEHBHTIFQYMOWCHqRWBMdTbdVSa3Ru%2BuPrdqggtBRQoRnz05trrG6m7FZw0hONsYZckvayNz3Om9uh1RscBTBEUzaWXYdZeB7zjyPUMk';
