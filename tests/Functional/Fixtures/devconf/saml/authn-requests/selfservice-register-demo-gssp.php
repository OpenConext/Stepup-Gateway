<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: selfservice-register-demo-gssp
 * Generated: 2026-11-04T18:00:49+00:00
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
 *   - SAMLRequest: Decoded (877 bytes XML)
 *   - Issuer: https://selfservice.dev.openconext.local/registration/gssf/demo_gssp/metadata
 *   - Destination: https://gateway.dev.openconext.local/gssp/demo_gssp/single-sign-on
 *   - ID: _2df589acdaeb8ac66624e48934b08137b4cd2a753cc1fc954793a7c7031f
 *   - IssueInstant: 2026-11-04T17:51:51Z
 *   - AssertionConsumerServiceURL: https://selfservice.dev.openconext.local/registration/gssf/demo_gssp/consume-assertion
 *   - RelayState: isGssfRequest
 */

/*
Decoded XML:

<?xml version="1.0" encoding="UTF-8"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_2df589acdaeb8ac66624e48934b08137b4cd2a753cc1fc954793a7c7031f" Version="2.0" IssueInstant="2026-11-04T17:51:51Z" Destination="https://gateway.dev.openconext.local/gssp/demo_gssp/single-sign-on" AssertionConsumerServiceURL="https://selfservice.dev.openconext.local/registration/gssf/demo_gssp/consume-assertion" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://selfservice.dev.openconext.local/registration/gssf/demo_gssp/metadata</saml:Issuer>
  <samlp:Extensions>
    <gssp:UserAttributes xmlns:gssp="urn:mace:surf.nl:stepup:gssp-extensions" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema"/>
  </samlp:Extensions>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=rVNNj9owEL3vr4h8z3cgwYKsaLdVkVgVLVBVvayMMwmWEjv1OIT%2B%2B%2BYDuqy22l4q%2BWB55s2b92Y8vz9XpXUCjULJBfEdj1ggucqELBZkv%2FtsJ%2BQ%2BvZsjq8qaLhtzlE%2FwswE0VgeUSIfAgjRaUsVQIJWsAqSG0%2B3ycU0Dx6O1VkZxVZIbyPsIhgjadB0Ra%2FWwIM9Blk%2BSGeMZg0PC%2BHQ6DSKIklkYHbzED%2BNDxLOAxZOQcz%2Fns0kUz0IW89gL%2FZxY367qgl7dCrGBlUTDpOmevGBq%2B77tRTs%2FphO%2FOz%2BI9dDpE5KZAXU0pkbqugUz0LJfTgYnR9W9SRLOxikVZ6VbINZuBpV6Hm7Y2VeCjaKQdq9ieRX0UUlsKtBb0CfBYf%2B0fiFAKHMc3%2F9OoqEQaPTQV8%2BY3zDysbB9Y93m4vsHIcdxvmf5YUxC%2BmW329ibr9sdSYep08Ewnf6XLiswLGOGzd3b0pft%2BnQ2IPtJYTrvs%2Bm%2B41kao8WhMYCX7ekjo5SKcaDY6NyRJUUDdVMPURv%2BFLqu3BnF6HMnoG1bpw0dpQs38Dzf%2Ff643vIjVMwWw1ZweEH9G0TcdBTzun%2F37YdJ734D&RelayState=isGssfRequest&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=U%2Bbyg5CN%2Bqw3sO0MN7RPj0z7zTMyjLDTmMDlvR5OiKLm122M%2BbfpT6R%2BRtr3TaIR7OAfomhJjHHNqjjqpK5gu26JtrUYr9fZebiLvLYqP5l9rAMam6OC3KLDVpgrSeSohJGt4AMXmhnz9uAQv8GtQXHnvp%2FMVODrAT6KmPu246GkK7m8uUwDbh7Dg8%2B8qlLjRIRGL%2B0FK1b39pPdmKDJUpmRRd1WLFn994AWmlNih6qS3NNgg4evn9FGy4b42zrzgjFFGAbj0%2FdgIc6yYiGwJ3rUABLq1cQYqxZouz7gJl5fjZwk9Ade2HQsKpFthM2sntZ1ZM5d1T0519hbK2A%2Fz5Sf3xhD2WA0p7%2FE9bZYIR4fOyr0rz3Yc43A1RcJAYwT7oPSo5TAsR%2FVHLIvbwV4wRfKYKLrU0VsLlHfIB2QQESXxk1tLld0QSxyI%2B2MfdFkLqUMMCjuwYk8r2IZGjB%2FiQg0R%2FbU4bj08eiqD7u73RU8DTELW1pE1VQ8IOtv%2BUET';
