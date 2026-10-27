<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: sfo-redirect-jane-a1-loa2
 * Generated: 2026-10-27T10:04:28+00:00
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
 *   - SAMLRequest: Decoded (1054 bytes XML)
 *   - Issuer: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/second-sp
 *   - Destination: https://gateway.dev.openconext.local/second-factor-only/single-sign-on
 *   - ID: _6bdce82ed03a19fc6776c85ed5080deacf2d011f4f
 *   - IssueInstant: 2026-10-27T10:02:36Z
 *   - NameID: urn:collab:person:institution-a.example.com:jane-a1
 *   - AuthnContextClassRef: http://dev.openconext.local/assurance/sfo-level2
 *   - AssertionConsumerServiceURL: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/second-sp
 *   - RelayState: https://ssp.dev.openconext.local/simplesaml/sp.php?sp=second-sp
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_6bdce82ed03a19fc6776c85ed5080deacf2d011f4f" Version="2.0" IssueInstant="2026-10-27T10:02:36Z" Destination="https://gateway.dev.openconext.local/second-factor-only/single-sign-on" AssertionConsumerServiceURL="https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/second-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/second-sp</saml:Issuer>
  <saml:Subject>
    <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:institution-a.example.com:jane-a1</saml:NameID>
  </saml:Subject>
  <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://dev.openconext.local/assurance/sfo-level2</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=pVTbbhoxEP2Vld%2F3mgaoBUgUVBUpbRHQPuSlMvYsOPLarsdL4O%2FrvUQFNSQPfVrNzefMmZkdI6uUpbPaH%2FQafteAPjpVSiNtAxNSO00NQ4lUswqQek43s68PtEgyap3xhhtFLkrermCI4Lw0mkTLxYT8GuwEh1EBIrtj%2BceSD4bDAR%2Fdg7jPRpkAxstCZHlefihJ9BMchsoJCQ%2BFcsQalho90z64smIQ51lcDLd5RrOC3g0eSbQI3UjNfFt18N4iTdM98%2FDMzomAY2IsaG40nHyiDGcqRQimiEvGvXGx0eqcotR7BTHKvY4b3rOXFuZGY12B24A7Sg4%2F1g9%2FQRDtDQBZWQWNUGllRK0gsQebtjZ23yJmHDtvRwYtiVa90p%2BkFoHP2yLvuiSkX7bbVbz6vtmS6bh5mraiuel%2FsqzAM8E8uyY5Ti8hOrxNvXsC7nvrW2C6XESfjauYv91CnuStR4Y5tKm01miBy1KCINOmLCih2I7asBFGUxm2QPq6mUnMEjixhnzCTUWfmIaY5T21Dn%2FaW1fcbB9cGSX5%2BV2KjcrXFL1jGiVoHxZEKfM8dxD2bEK8q4GkLyD9hYFo7y0skA%2Ba9%2BpcuuYqHMoaynZSYVCvDimk1AGVQ4qliRUcQRV9b6%2B%2B1cVukkj%2F%2FRNM%2FwA%3D&RelayState=https%3A%2F%2Fssp.dev.openconext.local%2Fsimplesaml%2Fsp.php%3Fsp%3Dsecond-sp&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=dn8ntljD17Q7RXfuiMgD70vV%2BgJz6suBPiloHbxGoRvMFTcpCK9GxEsWgiPXWCwy93Y2AJoAed%2FzHOT70TVuvls2u4L1QB7krvRmcqWUwDDS4S20AiHE8HCiR1fOyevLSWKbKUZrargdmuk%2Bys9izoxD8lgSUPfXBmH27pUX%2FvvYFbbj8XAuDNmg%2F3rML0BdyLobGGK1Ww%2BciLd0RL04l1Qe5uLXwQZ8y4CmOobSyF1nHFCaF6N7KyMk9aSFG2VgmuKfX6pcMIj64PHNY%2B62RJikqUTWUBvR5gT6o4rTcHzo15uFqsVsxYi2GlU9Z1kfgTMfgrV0HZ5rTosazhBGF8L7JHBJHPEFI9R00Ohqu%2B3ncyWtJJBpAPpe2KfrgWcJar8%2BKHvKHOavJjai8rEmE1FIco7OXE7LsT5EjUOo6HeCSCTmsZ0vt3cfj38X5rWVYtLPltBkbEsYmmg7Pwsked3ArSGBtYJhvUysP2I23cpHcqS9ugYpb%2BSV%2FyDNA%2FRq';
