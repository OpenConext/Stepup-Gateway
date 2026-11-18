<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: sso-redirect-selfservice
 * Generated: 2026-11-04T19:00:16+00:00
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
 *   - SAMLRequest: Decoded (641 bytes XML)
 *   - Issuer: https://selfservice.dev.openconext.local/authentication/metadata
 *   - Destination: https://gateway.dev.openconext.local/authentication/single-sign-on
 *   - ID: _f4838c83a1c3534132cd7db4eaade4b91a7a91d0beb73780000cb2b7175b
 *   - IssueInstant: 2026-11-04T18:47:34Z
 *   - AssertionConsumerServiceURL: https://selfservice.dev.openconext.local/authentication/consume-assertion
 */

/*
Decoded XML:

<?xml version="1.0" encoding="UTF-8"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_f4838c83a1c3534132cd7db4eaade4b91a7a91d0beb73780000cb2b7175b" Version="2.0" IssueInstant="2026-11-04T18:47:34Z" Destination="https://gateway.dev.openconext.local/authentication/single-sign-on" AssertionConsumerServiceURL="https://selfservice.dev.openconext.local/authentication/consume-assertion" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://selfservice.dev.openconext.local/authentication/metadata</saml:Issuer>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=nZJNb8IwDIbv%2FIoq936kLWuJ%2BBAbmobENARlh10mNzVQqU1YnbLt36%2B0Q2MXNC232Hltv48znHyUhXXEinKtRow7HrNQSZ3lajdim%2BTejtlk3BsSlMVBTGuzVyt8q5GM1QgViTYxYnWlhAbKSSgokYSRYj19XAjf8cSh0kZLXbALyXUFEGFlmomYNZ%2BN2Os2jINYxgFwGfSDkAe%2BzKIsDREgwzAdcIhgwDMvxTQKothrjkz9NOJRP2XW89mdf3I3J6pxrsiAMk3I829szm0vTHgswkgE4QuzZo2%2FXIFpVXtjDiRcdwcG3%2BHTyfDo6MMJksIP4xRaQuFCQwaVyWUrcqnBV6BN%2BU7ZJxfTs6E7ragusVpjdcwlblaLnwaExZa6%2BJ%2BayK6WfUFr%2BY36NlfdBq9RTrtHJB6SZGkvn9YJG7eLFi2javzfwUo0kIGBoXtZrbv9%2FkTj3hc%3D&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=nSi8Ewcpr8%2B%2FYKespEZsUg0i4KhPuMLZHBoZDYiV6LtatA6sy8cytmuzGY%2Fz2rOISJU%2FmZ%2Bt6tMoSvGWhoG4nhlkRRATDI3ePBP2a4NYdr4mMhtLHVsxyOebaxzitziAKSBXys8CzTtCvb8lDUomgemALdr4p4oQb4XSJzMBNkSqSHKvnXwU6%2FcVKy0SECBIv4PcaYkASCJKdu1Q32f0oMjlUIODpcL8xEoTe6Dt6IKftThCAmR38MB52BAmxIKj3lI09WHpDrSWxtGicEuUzZxQAuMrb5N76z76wwuS%2BhWy%2B1fys1jBbSrOkoeUHTG9zw7WbTOynYRno4lXnSfeFIq5e5TeQJm1db3nyDZ8C8jNT4ydCbZRv%2BCTXerVPqlG6140LiM%2BRN3NX3Iyf1mdRr3SE7fmqDiLPXxCdvqzORkPiDafTQcV1oGAdnM%2FUaoOqFnopYCKvN9X%2BavE41h7%2Fv4jx%2FL1BQ2%2FUx7Q91UxlUdUb%2FNOpu%2BfSTy1BYuZrCFJ';
