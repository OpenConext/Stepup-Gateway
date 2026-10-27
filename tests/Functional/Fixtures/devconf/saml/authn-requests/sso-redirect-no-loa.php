<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: sso-redirect-joe-a-yk
 * Generated: 2026-10-27T10:36:34+00:00
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
 *   - SAMLRequest: Decoded (720 bytes XML)
 *   - Issuer: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/default-sp
 *   - Destination: https://gateway.dev.openconext.local/authentication/single-sign-on
 *   - ID: _6eaeef41ba278258cbfce268307294bf89889a96b6
 *   - IssueInstant: 2026-10-27T10:34:40Z
 *   - AssertionConsumerServiceURL: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp
 *   - RelayState: https://ssp.dev.openconext.local/simplesaml/sp.php?sp=default-sp
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_6eaeef41ba278258cbfce268307294bf89889a96b6" Version="2.0" IssueInstant="2026-10-27T10:34:40Z" Destination="https://gateway.dev.openconext.local/authentication/single-sign-on" AssertionConsumerServiceURL="https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer>
  <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=pZJNb9swDIb%2FiqG7P%2BJmjiMkAbIGwwJ0W9CkPewy0DKdCJAlTaT78e%2BnOBvWDUMuOwmi%2BJKPXnJB0Bsv1wOf7D1%2BH5A4eemNJTk%2BLMUQrHRAmqSFHkmykvv1pztZZoX0wbFTzog3kusKIMLA2lmRbDdL8a1CQOymkwbKWV2%2Bq1XTKSyr%2BqaYlfNp09Xzup7DvGoqkTxioKhcilgoyokG3FpisBxDRVmlkyItZ4dJIW%2Bmclp8Fckm%2FkZb4FF1YvYk8%2FwIjM%2FwmrX4lDmPVjmLL5wZp8DkEH1Ay1qNopy0PRpMSR9temZe%2F8K%2FdZaGHsMew5NW%2BHB%2F97sBkf93cdK9N3g2Ke9dOxjM%2FMnn450uZ5mCojHaYgeD4ZS8SHY%2FbX6vbRuBrjvcXJJIfjwcdunuy%2F4gVotzbTk6Flb%2FidkjQwsMf1Eu8rc9Fpet%2BhzptpudM1q9Jh9c6IGvw58juk27MVVyAEs6jiMab4x7vg0YZ7cUHAYU%2BerS8s%2FdXf0A&RelayState=https%3A%2F%2Fssp.dev.openconext.local%2Fsimplesaml%2Fsp.php%3Fsp%3Ddefault-sp&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=UHYmENqeHdMxCExlwLRk0FhtQAP7IxR8GzB%2BsGhEKTAF2hihHAe%2BHEILqD3ubMsObxIxIN%2FstNqAjc9zzYb1qPeYftOU85%2FtffrBlydBaO6R2sCgtVVVW%2FzSCRwkkw9bjwXU5UCk34hrH75ZlpPVqSlXWGlQqJ2%2BuaX06fLfv8O5wldpHmdqOri1FXvMEt3vXUhO%2Bp1LTq5hvEIhSJBRR7Q9bnQQ%2F9ycLGuH08sOh%2BZifVT9ycQTZeaftW6BDRS6wQNbs3mvphnfL6jPixZLijH7O1HKX3G7lbtNw5Kz2vg3kF%2FjRTfVpfSY2wcjKGC6ZLYHL2rderGUYu%2F96i4tIckHGGLqYnM3j1ftKCyd%2FlfbqfABWljkx6HYb%2BuFhyNy5vz4DbYeOhk%2BTTt4DpeqlWHmbvXUvp0%2Bvu396SYwM5%2FY7Q43XjgPuZi75bpPBWZKt7CbyydVILYSv4zdbTnEXFvWWlYKJO7QmRrxTTmKm22LQ7RENUA4aE9Peo3T62NZ';
