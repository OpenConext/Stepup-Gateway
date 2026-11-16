<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: sso-redirect-loa2-forceauthn
 * Generated: 2026-11-05T19:19:47+00:00
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
 *   - SAMLRequest: Decoded (894 bytes XML)
 *   - Issuer: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/default-sp
 *   - Destination: https://gateway.dev.openconext.local/authentication/single-sign-on
 *   - ID: _fda0876a495382bc5ed6a858a3260830a23ea7b05e
 *   - IssueInstant: 2026-11-05T19:18:36Z
 *   - AuthnContextClassRef: http://dev.openconext.local/assurance/loa2
 *   - AssertionConsumerServiceURL: https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp
 *   - RelayState: https://ssp.dev.openconext.local/simplesaml/sp.php?sp=default-sp
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_fda0876a495382bc5ed6a858a3260830a23ea7b05e" Version="2.0" IssueInstant="2026-11-05T19:18:36Z" Destination="https://gateway.dev.openconext.local/authentication/single-sign-on" ForceAuthn="true" AssertionConsumerServiceURL="https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer>
  <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://dev.openconext.local/assurance/loa2</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=pVNLj9owEP4rke%2BJQ9JksxYgUVBVpG2LgPawl2pwBrDk2KnH2ce%2FrxNYFbUsl54ij%2F3N95jJmKDRrZh1%2FmjW%2BKtD8tFLow2J4WLCOmeEBVIkDDRIwkuxmX15EFmSitZZb6XV7AJyGwFE6LyyhkXLxYT93NeQVnclfLgv8irbyQLrEqqigjwr0ypPIcsR7nZpgSz6gY4CcsJCowAn6nBpyIPxoZRmZTwaxWmxHd2LUSXy8pFFi%2BBGGfAD6uh9S4LzA3h8htekxqfEtmikNfjiE20laA4hBzReyQHESZmDxpjUwcS95k%2FWSRyymjDvuiBq9mZobg11DboNuicl8fv64Q8lUXudjlTTauxj442tO41Je2z5cKbTN4tB0lCtcQ%2Bd9jG1LFqdg%2F%2BoTB0k3s58d3pE4vN2u4pX3zZbNh33vcWQoZv%2Bp8wGPdTg4S%2BVY37JMT7t2degbrlYWa3ka59mA%2F62%2BL6i6ng%2FPBXegSEVBhSC19o%2Bzx2GaZ5nwd9IznuM9TCpMBgfrJwtX5bmOqzjGvdDAMH%2F9Y0I%2BgOrRK4tZGdTV7uc7t6l5%2F%2F%2BadPf&RelayState=https%3A%2F%2Fssp.dev.openconext.local%2Fsimplesaml%2Fsp.php%3Fsp%3Ddefault-sp&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=Sf4Omc8Io5B3LHMirrf6Ta6pYZulbABxI8fLqd8nmttQ%2FtJbb9LyRMjZU1AhSp3Rg4vWBvro85sQFIzY4mJxzWvNE8WYuJ39Yn0KXu3kFyOPpLV1Gpdzh%2FMPREEhaN1BUGFfCLbrF8Blnl1NmNYwejnf9GjH8JpJjxlqbDNSKq6Q5rA9%2BQwc6NbH0NicjPNFdnWsR9nvUHX5ssUHz7JQy10oUTn%2Bd6bKEwTrrr6ZodlaIKY7HHHQTG8YsVG3Q9CdfldFkSiwelcBilz4KO0XEv0CUVEhhJZpGNozqDwEa7pVhzL88bdI3%2FOtYjfMBKsQl%2BCpN1oYZEm1l3t7r8YzCAIIaRImhChOdVqNsTSfq05wKaYr6oAMFIIskYuJfbSh6hfbGxHd%2Fw0rtl7tKi35RkV8DkPu99lLO3rS1lf6vrTjDaeaFpCwG90IyJzNjlClXSxYSxxVDhfdfIbWC3hQBiYuo30YV1sKYABG5rA7FNsAqq%2FTt%2FvF%2BCVsrGgslwYm';
