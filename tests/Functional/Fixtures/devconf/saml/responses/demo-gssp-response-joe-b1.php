<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: response-post
 * Binding: HTTP-POST
 * Fixture name: demo-gssp-response-joe-b1
 * Generated: 2026-11-04T18:02:42+00:00
 *
 * This is an HTTP-POST Response (base64-encoded XML).
 * Captured from: SAML Tracer or browser DevTools form data
 * Format: Base64-encoded XML value of SAMLResponse parameter
 * Note: HTTP-Redirect Response does not exist - responses always use HTTP-POST
 *
 * Message Summary:
 *   - Binding: HTTP-POST (base64-encoded XML)
 *   - XML size: 4767 bytes
 *   - Signed: Yes (XML signature)
 *   - Signature Algorithm: http://www.w3.org/2001/04/xmldsig-more#rsa-sha256
 *   - Issuer: https://demogssp.dev.openconext.local/saml/metadata
 *   - NameID: test-prefix-6aeb70b7660b91.34464221
 *   - AuthnContextClassRef: urn:oasis:names:tc:SAML:2.0:ac:classes:MobileTwoFactorUnregistered
 *   - StatusCode: urn:oasis:names:tc:SAML:2.0:status:Success
 *   - Audience: https://gateway.dev.openconext.local/gssp/demo_gssp/metadata
 */

/*
Decoded XML:

<?xml version="1.0" encoding="UTF-8"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_3e09a24864ced13fc38fc2ea51a1a7c247f35ff93cc112380886254acb2f" Version="2.0" IssueInstant="2026-11-04T17:51:54Z" Destination="https://gateway.dev.openconext.local/gssp/demo_gssp/consume-assertion" InResponseTo="_fc66d30c1f59d6033413759e1d2b2f1e642098ed03fdfdcfa1f9bdeee208">
  <saml:Issuer>https://demogssp.dev.openconext.local/saml/metadata</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="_0e5b7fc7287d434a089f56e8b7c238e315523c5e8bcc57cd05a714c6fb09" Version="2.0" IssueInstant="2026-11-04T17:51:54Z">
    <saml:Issuer>https://demogssp.dev.openconext.local/saml/metadata</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#_0e5b7fc7287d434a089f56e8b7c238e315523c5e8bcc57cd05a714c6fb09">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>yKq5ZQtLDLc9+CAyfu7WqprXc7+YcZ0y0NHMf6kJtj4=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>P4indukOEN9TzE97drub7mschCqQpladW7HJtI4wQaDCN0gKEBCp73Oz5Y40UepMpzirwkCvj9I+0tNRPM1EQSD3FdWq4IDNyHVtOZbSIvrcs5W5EP/vesrifdklSSSzXmadFRRgnAzHL2soMmdVyfOm7M3urzzx3cVv79K7vWHXvZ6uni8BNgZeYmNHv675OYUwcfJGBEcaev09OFpTIVr+WqOG3xWMJ7Nweyd8TCQdMQcmR/Trz56tH70HfqsJm9T63cH9ri6wRi9cMf2LUVqJ7Z0qCH6rvkfvQKDRa2upsfPQ4uKAz4+s0W01XnDJ5o7P1nf/W6YQsprobxOxcdVN4anPcITe0Mzg4mDNt+5mp2MV+0N4j23cndVlRfB4TDo6XKsD6bo8wjaiJvUgnu2zXJpOAVXTGunwkFJ9t6A/JjqTiru2CqKfoB4rpXM1mqpwT6EhF+iW6VO88NN7AYVH6+3f2bsbCPFWBjimw8xBtkgUjc3C0wgqBpBEDE/5</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MIIECTCCAnECFF1oAXk2cQFyPcHSQZWw6zflwNDvMA0GCSqGSIb3DQEBCwUAMEExFjAUBgNVBAMMDURlbW8gR1NTUCBJZFAxJzAlBgNVBAoMHkRldmVsb3BtZW50IERvY2tlciBlbnZpcm9ubWVudDAeFw0yMzA1MjUwOTMzMjRaFw0yODA1MjMwOTMzMjRaMEExFjAUBgNVBAMMDURlbW8gR1NTUCBJZFAxJzAlBgNVBAoMHkRldmVsb3BtZW50IERvY2tlciBlbnZpcm9ubWVudDCCAaIwDQYJKoZIhvcNAQEBBQADggGPADCCAYoCggGBALYkxk++tFTkX7HSTMZ1Qe/P/p9ErUTNgxis8+0MRlMU9RVCoamEtHDD2kRhYw21n3zNfolMnwXcuTFA2B95wrnegghO/+P9bUGPYj11IakPdKOPIoxy5E11DdVR4usVAIaRjpSRuOzSKA+qhy11o5YZh23KJdRSGVZibzXR8qWexu+OKwqI0l/Yh1Gmbg6078VA+ud6uuP+STCNYrITus9BAZUjg//9gOa96GzLGvirHKpiXx3e3gqc54sVTy3rAekTQQu3MEEdhA/jVeI9SXhnBE7AMc8drzrqwbW7xK905+H3I3IOsaCGD1WdZwwFUVdRQ8+SWla6Fvcu/+D/8PsyuKDzc92dz40yKn7ccvSSU8CW3P1WwvIcA9saEIiHak0WMbCN5Mok6OBfrYGu1ZST2PN/d4AQQKA5Zjk76iqEZepT9O2cuSKAvoOZVSlHrEq9Yp7+COepQ0pqYcX/+YMdqaeM71XBT6V+7P5CuF4pBNA91pE6W5qKQ2uDOJESwwIDAQABMA0GCSqGSIb3DQEBCwUAA4IBgQCdluyWERbSDlY3bs16nCk23BIktp1MTsV3vbWBr3w35rPVkQ5XeB9OrSTcgP2XaF6JNx2eMIa+7Npabe2m0AAMt/2vVTAmsg+/KpVdPLPzA757DSd99EcoMnJGTGSSLo0FzV+oMNwxWV0R9h7KdhbrGMILAaHSC+S+z+7dYqITK5bHcTFm9wkdqAVAqge1HT7A/7AZgM3fSXaBHC0IPo1viFn/c+C0ckD+H0woqMJy34QtsgtvvTzVJxlbAEcGlIm2+mWdzzi54H/OSYp+tR+oJhhvPd74jDNa42QpAS7euyz2CyI/b5kisRUST6NR/9Jp1ix36TYwoJ2RIjxDZph/ndGK+1eb7dQd/kcgN/jOxy49YxwTtnh0dabKf1nCq/t6HmMWGPXBURaMMB9hk5b6v8+aTGphjQEr/1nib5hiWU23/+EN/F6LlJhqKQVSRP9qM05GXbQmfRTaXuAmj76sgGGw/QQee6zuXuR9WPaUlXGegVeH8iqUa/H6rhOcToE=</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">test-prefix-6aeb70b7660b91.34464221</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData NotOnOrAfter="2026-11-04T17:56:54Z" Recipient="https://gateway.dev.openconext.local/gssp/demo_gssp/consume-assertion" InResponseTo="_fc66d30c1f59d6033413759e1d2b2f1e642098ed03fdfdcfa1f9bdeee208"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2026-11-04T17:51:54Z" NotOnOrAfter="2026-11-04T17:56:54Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://gateway.dev.openconext.local/gssp/demo_gssp/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement AuthnInstant="2026-11-04T17:51:54Z">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:MobileTwoFactorUnregistered</saml:AuthnContextClassRef>
        <saml:AuthenticatingAuthority>https://demogssp.dev.openconext.local/saml/metadata</saml:AuthenticatingAuthority>
      </saml:AuthnContext>
    </saml:AuthnStatement>
  </saml:Assertion>
</samlp:Response>
*/

return 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHNhbWxwOlJlc3BvbnNlIHhtbG5zOnNhbWxwPSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6cHJvdG9jb2wiIHhtbG5zOnNhbWw9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphc3NlcnRpb24iIElEPSJfM2UwOWEyNDg2NGNlZDEzZmMzOGZjMmVhNTFhMWE3YzI0N2YzNWZmOTNjYzExMjM4MDg4NjI1NGFjYjJmIiBWZXJzaW9uPSIyLjAiIElzc3VlSW5zdGFudD0iMjAyNi0xMS0wNFQxNzo1MTo1NFoiIERlc3RpbmF0aW9uPSJodHRwczovL2dhdGV3YXkuZGV2Lm9wZW5jb25leHQubG9jYWwvZ3NzcC9kZW1vX2dzc3AvY29uc3VtZS1hc3NlcnRpb24iIEluUmVzcG9uc2VUbz0iX2ZjNjZkMzBjMWY1OWQ2MDMzNDEzNzU5ZTFkMmIyZjFlNjQyMDk4ZWQwM2ZkZmRjZmExZjliZGVlZTIwOCI+PHNhbWw6SXNzdWVyPmh0dHBzOi8vZGVtb2dzc3AuZGV2Lm9wZW5jb25leHQubG9jYWwvc2FtbC9tZXRhZGF0YTwvc2FtbDpJc3N1ZXI+PHNhbWxwOlN0YXR1cz48c2FtbHA6U3RhdHVzQ29kZSBWYWx1ZT0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOnN0YXR1czpTdWNjZXNzIi8+PC9zYW1scDpTdGF0dXM+PHNhbWw6QXNzZXJ0aW9uIHhtbG5zOnhzaT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS9YTUxTY2hlbWEtaW5zdGFuY2UiIHhtbG5zOnhzPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxL1hNTFNjaGVtYSIgSUQ9Il8wZTViN2ZjNzI4N2Q0MzRhMDg5ZjU2ZThiN2MyMzhlMzE1NTIzYzVlOGJjYzU3Y2QwNWE3MTRjNmZiMDkiIFZlcnNpb249IjIuMCIgSXNzdWVJbnN0YW50PSIyMDI2LTExLTA0VDE3OjUxOjU0WiI+PHNhbWw6SXNzdWVyPmh0dHBzOi8vZGVtb2dzc3AuZGV2Lm9wZW5jb25leHQubG9jYWwvc2FtbC9tZXRhZGF0YTwvc2FtbDpJc3N1ZXI+PGRzOlNpZ25hdHVyZSB4bWxuczpkcz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnIyI+CiAgPGRzOlNpZ25lZEluZm8+PGRzOkNhbm9uaWNhbGl6YXRpb25NZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzEwL3htbC1leGMtYzE0biMiLz4KICAgIDxkczpTaWduYXR1cmVNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGRzaWctbW9yZSNyc2Etc2hhMjU2Ii8+CiAgPGRzOlJlZmVyZW5jZSBVUkk9IiNfMGU1YjdmYzcyODdkNDM0YTA4OWY1NmU4YjdjMjM4ZTMxNTUyM2M1ZThiY2M1N2NkMDVhNzE0YzZmYjA5Ij48ZHM6VHJhbnNmb3Jtcz48ZHM6VHJhbnNmb3JtIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnI2VudmVsb3BlZC1zaWduYXR1cmUiLz48ZHM6VHJhbnNmb3JtIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS8xMC94bWwtZXhjLWMxNG4jIi8+PC9kczpUcmFuc2Zvcm1zPjxkczpEaWdlc3RNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGVuYyNzaGEyNTYiLz48ZHM6RGlnZXN0VmFsdWU+eUtxNVpRdExETGM5K0NBeWZ1N1dxcHJYYzcrWWNaMHkwTkhNZjZrSnRqND08L2RzOkRpZ2VzdFZhbHVlPjwvZHM6UmVmZXJlbmNlPjwvZHM6U2lnbmVkSW5mbz48ZHM6U2lnbmF0dXJlVmFsdWU+UDRpbmR1a09FTjlUekU5N2RydWI3bXNjaENxUXBsYWRXN0hKdEk0d1FhRENOMGdLRUJDcDczT3o1WTQwVWVwTXB6aXJ3a0N2ajlJKzB0TlJQTTFFUVNEM0ZkV3E0SUROeUhWdE9aYlNJdnJjczVXNUVQL3Zlc3JpZmRrbFNTU3pYbWFkRlJSZ25BekhMMnNvTW1kVnlmT203TTN1cnp6eDNjVnY3OUs3dldIWHZaNnVuaThCTmdaZVltTkh2Njc1T1lVd2NmSkdCRWNhZXYwOU9GcFRJVnIrV3FPRzN4V01KN053ZXlkOFRDUWRNUWNtUi9Ucno1NnRINzBIZnFzSm05VDYzY0g5cmk2d1JpOWNNZjJMVVZxSjdaMHFDSDZydmtmdlFLRFJhMnVwc2ZQUTR1S0F6NCtzMFcwMVhuREo1bzdQMW5mL1c2WVFzcHJvYnhPeGNkVk40YW5QY0lUZTBNemc0bUROdCs1bXAyTVYrME40ajIzY25kVmxSZkI0VERvNlhLc0Q2Ym84d2phaUp2VWdudTJ6WEpwT0FWWFRHdW53a0ZKOXQ2QS9KanFUaXJ1MkNxS2ZvQjRycFhNMW1xcHdUNkVoRitpVzZWTzg4Tk43QVlWSDYrM2YyYnNiQ1BGV0JqaW13OHhCdGtnVWpjM0Mwd2dxQnBCRURFLzU8L2RzOlNpZ25hdHVyZVZhbHVlPgo8ZHM6S2V5SW5mbz48ZHM6WDUwOURhdGE+PGRzOlg1MDlDZXJ0aWZpY2F0ZT5NSUlFQ1RDQ0FuRUNGRjFvQVhrMmNRRnlQY0hTUVpXdzZ6Zmx3TkR2TUEwR0NTcUdTSWIzRFFFQkN3VUFNRUV4RmpBVUJnTlZCQU1NRFVSbGJXOGdSMU5UVUNCSlpGQXhKekFsQmdOVkJBb01Ia1JsZG1Wc2IzQnRaVzUwSUVSdlkydGxjaUJsYm5acGNtOXViV1Z1ZERBZUZ3MHlNekExTWpVd09UTXpNalJhRncweU9EQTFNak13T1RNek1qUmFNRUV4RmpBVUJnTlZCQU1NRFVSbGJXOGdSMU5UVUNCSlpGQXhKekFsQmdOVkJBb01Ia1JsZG1Wc2IzQnRaVzUwSUVSdlkydGxjaUJsYm5acGNtOXViV1Z1ZERDQ0FhSXdEUVlKS29aSWh2Y05BUUVCQlFBRGdnR1BBRENDQVlvQ2dnR0JBTFlreGsrK3RGVGtYN0hTVE1aMVFlL1AvcDlFclVUTmd4aXM4KzBNUmxNVTlSVkNvYW1FdEhERDJrUmhZdzIxbjN6TmZvbE1ud1hjdVRGQTJCOTV3cm5lZ2doTy8rUDliVUdQWWoxMUlha1BkS09QSW94eTVFMTFEZFZSNHVzVkFJYVJqcFNSdU96U0tBK3FoeTExbzVZWmgyM0tKZFJTR1ZaaWJ6WFI4cVdleHUrT0t3cUkwbC9ZaDFHbWJnNjA3OFZBK3VkNnV1UCtTVENOWXJJVHVzOUJBWlVqZy8vOWdPYTk2R3pMR3ZpckhLcGlYeDNlM2dxYzU0c1ZUeTNyQWVrVFFRdTNNRUVkaEEvalZlSTlTWGhuQkU3QU1jOGRyenJxd2JXN3hLOTA1K0gzSTNJT3NhQ0dEMVdkWnd3RlVWZFJROCtTV2xhNkZ2Y3UvK0QvOFBzeXVLRHpjOTJkejQweUtuN2NjdlNTVThDVzNQMVd3dkljQTlzYUVJaUhhazBXTWJDTjVNb2s2T0JmcllHdTFaU1QyUE4vZDRBUVFLQTVaams3NmlxRVplcFQ5TzJjdVNLQXZvT1pWU2xIckVxOVlwNytDT2VwUTBwcVljWC8rWU1kcWFlTTcxWEJUNlYrN1A1Q3VGNHBCTkE5MXBFNlc1cUtRMnVET0pFU3d3SURBUUFCTUEwR0NTcUdTSWIzRFFFQkN3VUFBNElCZ1FDZGx1eVdFUmJTRGxZM2JzMTZuQ2syM0JJa3RwMU1Uc1YzdmJXQnIzdzM1clBWa1E1WGVCOU9yU1RjZ1AyWGFGNkpOeDJlTUlhKzdOcGFiZTJtMEFBTXQvMnZWVEFtc2crL0twVmRQTFB6QTc1N0RTZDk5RWNvTW5KR1RHU1NMbzBGelYrb01Od3hXVjBSOWg3S2RoYnJHTUlMQWFIU0MrUyt6KzdkWXFJVEs1YkhjVEZtOXdrZHFBVkFxZ2UxSFQ3QS83QVpnTTNmU1hhQkhDMElQbzF2aUZuL2MrQzBja0QrSDB3b3FNSnkzNFF0c2d0dnZUelZKeGxiQUVjR2xJbTIrbVdkenppNTRIL09TWXArdFIrb0poaHZQZDc0akROYTQyUXBBUzdldXl6MkN5SS9iNWtpc1JVU1Q2TlIvOUpwMWl4MzZUWXdvSjJSSWp4RFpwaC9uZEdLKzFlYjdkUWQva2NnTi9qT3h5NDlZeHdUdG5oMGRhYktmMW5DcS90NkhtTVdHUFhCVVJhTU1COWhrNWI2djgrYVRHcGhqUUVyLzFuaWI1aGlXVTIzLytFTi9GNkxsSmhxS1FWU1JQOXFNMDVHWGJRbWZSVGFYdUFtajc2c2dHR3cvUVFlZTZ6dVh1UjlXUGFVbFhHZWdWZUg4aXFVYS9INnJoT2NUb0U9PC9kczpYNTA5Q2VydGlmaWNhdGU+PC9kczpYNTA5RGF0YT48L2RzOktleUluZm8+PC9kczpTaWduYXR1cmU+PHNhbWw6U3ViamVjdD48c2FtbDpOYW1lSUQgRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6bmFtZWlkLWZvcm1hdDpwZXJzaXN0ZW50Ij50ZXN0LXByZWZpeC02YWViNzBiNzY2MGI5MS4zNDQ2NDIyMTwvc2FtbDpOYW1lSUQ+PHNhbWw6U3ViamVjdENvbmZpcm1hdGlvbiBNZXRob2Q9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDpjbTpiZWFyZXIiPjxzYW1sOlN1YmplY3RDb25maXJtYXRpb25EYXRhIE5vdE9uT3JBZnRlcj0iMjAyNi0xMS0wNFQxNzo1Njo1NFoiIFJlY2lwaWVudD0iaHR0cHM6Ly9nYXRld2F5LmRldi5vcGVuY29uZXh0LmxvY2FsL2dzc3AvZGVtb19nc3NwL2NvbnN1bWUtYXNzZXJ0aW9uIiBJblJlc3BvbnNlVG89Il9mYzY2ZDMwYzFmNTlkNjAzMzQxMzc1OWUxZDJiMmYxZTY0MjA5OGVkMDNmZGZkY2ZhMWY5YmRlZWUyMDgiLz48L3NhbWw6U3ViamVjdENvbmZpcm1hdGlvbj48L3NhbWw6U3ViamVjdD48c2FtbDpDb25kaXRpb25zIE5vdEJlZm9yZT0iMjAyNi0xMS0wNFQxNzo1MTo1NFoiIE5vdE9uT3JBZnRlcj0iMjAyNi0xMS0wNFQxNzo1Njo1NFoiPjxzYW1sOkF1ZGllbmNlUmVzdHJpY3Rpb24+PHNhbWw6QXVkaWVuY2U+aHR0cHM6Ly9nYXRld2F5LmRldi5vcGVuY29uZXh0LmxvY2FsL2dzc3AvZGVtb19nc3NwL21ldGFkYXRhPC9zYW1sOkF1ZGllbmNlPjwvc2FtbDpBdWRpZW5jZVJlc3RyaWN0aW9uPjwvc2FtbDpDb25kaXRpb25zPjxzYW1sOkF1dGhuU3RhdGVtZW50IEF1dGhuSW5zdGFudD0iMjAyNi0xMS0wNFQxNzo1MTo1NFoiPjxzYW1sOkF1dGhuQ29udGV4dD48c2FtbDpBdXRobkNvbnRleHRDbGFzc1JlZj51cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YWM6Y2xhc3NlczpNb2JpbGVUd29GYWN0b3JVbnJlZ2lzdGVyZWQ8L3NhbWw6QXV0aG5Db250ZXh0Q2xhc3NSZWY+PHNhbWw6QXV0aGVudGljYXRpbmdBdXRob3JpdHk+aHR0cHM6Ly9kZW1vZ3NzcC5kZXYub3BlbmNvbmV4dC5sb2NhbC9zYW1sL21ldGFkYXRhPC9zYW1sOkF1dGhlbnRpY2F0aW5nQXV0aG9yaXR5Pjwvc2FtbDpBdXRobkNvbnRleHQ+PC9zYW1sOkF1dGhuU3RhdGVtZW50Pjwvc2FtbDpBc3NlcnRpb24+PC9zYW1scDpSZXNwb25zZT4K';
