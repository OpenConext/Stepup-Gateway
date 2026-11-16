<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: response-post
 * Binding: HTTP-POST
 * Fixture name: demo-gssp-response-jane-b1
 * Generated: 2026-11-04T19:14:24+00:00
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
 *   - NameID: test-prefix-6aeb7de7519694.37754665
 *   - AuthnContextClassRef: urn:oasis:names:tc:SAML:2.0:ac:classes:MobileTwoFactorUnregistered
 *   - StatusCode: urn:oasis:names:tc:SAML:2.0:status:Success
 *   - Audience: https://gateway.dev.openconext.local/gssp/demo_gssp/metadata
 */

/*
Decoded XML:

<?xml version="1.0" encoding="UTF-8"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_14346a4442ce0b847dcb3e516f517463d5b0965f58fc3cde903a1fccba6d" Version="2.0" IssueInstant="2026-11-04T18:49:00Z" Destination="https://gateway.dev.openconext.local/gssp/demo_gssp/consume-assertion" InResponseTo="_08e4081d75256294e359b5b604e4f986636e62372d455aaa0dba456a814a">
  <saml:Issuer>https://demogssp.dev.openconext.local/saml/metadata</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="_4551e716b8aaf365199e2dae5b6fe7aa60dfa3be0854201cfa9547bce2cd" Version="2.0" IssueInstant="2026-11-04T18:49:00Z">
    <saml:Issuer>https://demogssp.dev.openconext.local/saml/metadata</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#_4551e716b8aaf365199e2dae5b6fe7aa60dfa3be0854201cfa9547bce2cd">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>M3cfTOHxL3c3TJ9vA0xECkf0O0RbGavqKTuDV7DtsCs=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>sjyZgJsTepy1dxjQMEds7nX6lXXc+QGtXR1C9g+y+8UCh79KSQ15vEqPs9hcI6641EMvTBUABsw9uzz3NLSwHvdrzJLSfLglcNYTn1jSe2x1whWVwfAXUk2nLoCUCgaPH9mxnpiPCXExxWu9Y54Dw7q7lv/r9E8DFlIyOErjrn8FhYg5QyIFUPKet9wG5iQN5TDU3Wwrq3bFiHBVOyL5ebTdbO8ntRkhK2TbOrCd1dAdiGpWKoH46b0PXDHavK/jLglpkaYBI4EmQ4TKmoFaGUxHOlBu5EPiG4YU180aiEFtR8vEsZDQRyjOcqa7HUmv+6u9K4qqiOjPYeFNLTEHfpFAcpC+TazXDENaaxz07hks4HCuKVtV5mJJlTLdyu9xEDv9ADBo9cEKryBV9S3o6NoXOwn2X7S0K4OzL3iQ9tg42jW68LrIYNAuWbdHLYDARgzwKS1b2CY8hy4VXDhWn+EKmm56hbE+h2E1pj6x8T1ZUmtvEewhiqWidDXeeENE</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MIIECTCCAnECFF1oAXk2cQFyPcHSQZWw6zflwNDvMA0GCSqGSIb3DQEBCwUAMEExFjAUBgNVBAMMDURlbW8gR1NTUCBJZFAxJzAlBgNVBAoMHkRldmVsb3BtZW50IERvY2tlciBlbnZpcm9ubWVudDAeFw0yMzA1MjUwOTMzMjRaFw0yODA1MjMwOTMzMjRaMEExFjAUBgNVBAMMDURlbW8gR1NTUCBJZFAxJzAlBgNVBAoMHkRldmVsb3BtZW50IERvY2tlciBlbnZpcm9ubWVudDCCAaIwDQYJKoZIhvcNAQEBBQADggGPADCCAYoCggGBALYkxk++tFTkX7HSTMZ1Qe/P/p9ErUTNgxis8+0MRlMU9RVCoamEtHDD2kRhYw21n3zNfolMnwXcuTFA2B95wrnegghO/+P9bUGPYj11IakPdKOPIoxy5E11DdVR4usVAIaRjpSRuOzSKA+qhy11o5YZh23KJdRSGVZibzXR8qWexu+OKwqI0l/Yh1Gmbg6078VA+ud6uuP+STCNYrITus9BAZUjg//9gOa96GzLGvirHKpiXx3e3gqc54sVTy3rAekTQQu3MEEdhA/jVeI9SXhnBE7AMc8drzrqwbW7xK905+H3I3IOsaCGD1WdZwwFUVdRQ8+SWla6Fvcu/+D/8PsyuKDzc92dz40yKn7ccvSSU8CW3P1WwvIcA9saEIiHak0WMbCN5Mok6OBfrYGu1ZST2PN/d4AQQKA5Zjk76iqEZepT9O2cuSKAvoOZVSlHrEq9Yp7+COepQ0pqYcX/+YMdqaeM71XBT6V+7P5CuF4pBNA91pE6W5qKQ2uDOJESwwIDAQABMA0GCSqGSIb3DQEBCwUAA4IBgQCdluyWERbSDlY3bs16nCk23BIktp1MTsV3vbWBr3w35rPVkQ5XeB9OrSTcgP2XaF6JNx2eMIa+7Npabe2m0AAMt/2vVTAmsg+/KpVdPLPzA757DSd99EcoMnJGTGSSLo0FzV+oMNwxWV0R9h7KdhbrGMILAaHSC+S+z+7dYqITK5bHcTFm9wkdqAVAqge1HT7A/7AZgM3fSXaBHC0IPo1viFn/c+C0ckD+H0woqMJy34QtsgtvvTzVJxlbAEcGlIm2+mWdzzi54H/OSYp+tR+oJhhvPd74jDNa42QpAS7euyz2CyI/b5kisRUST6NR/9Jp1ix36TYwoJ2RIjxDZph/ndGK+1eb7dQd/kcgN/jOxy49YxwTtnh0dabKf1nCq/t6HmMWGPXBURaMMB9hk5b6v8+aTGphjQEr/1nib5hiWU23/+EN/F6LlJhqKQVSRP9qM05GXbQmfRTaXuAmj76sgGGw/QQee6zuXuR9WPaUlXGegVeH8iqUa/H6rhOcToE=</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">test-prefix-6aeb7de7519694.37754665</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData NotOnOrAfter="2026-11-04T18:54:00Z" Recipient="https://gateway.dev.openconext.local/gssp/demo_gssp/consume-assertion" InResponseTo="_08e4081d75256294e359b5b604e4f986636e62372d455aaa0dba456a814a"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2026-11-04T18:49:00Z" NotOnOrAfter="2026-11-04T18:54:00Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://gateway.dev.openconext.local/gssp/demo_gssp/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement AuthnInstant="2026-11-04T18:49:00Z">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:MobileTwoFactorUnregistered</saml:AuthnContextClassRef>
        <saml:AuthenticatingAuthority>https://demogssp.dev.openconext.local/saml/metadata</saml:AuthenticatingAuthority>
      </saml:AuthnContext>
    </saml:AuthnStatement>
  </saml:Assertion>
</samlp:Response>
*/

return 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHNhbWxwOlJlc3BvbnNlIHhtbG5zOnNhbWxwPSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6cHJvdG9jb2wiIHhtbG5zOnNhbWw9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphc3NlcnRpb24iIElEPSJfMTQzNDZhNDQ0MmNlMGI4NDdkY2IzZTUxNmY1MTc0NjNkNWIwOTY1ZjU4ZmMzY2RlOTAzYTFmY2NiYTZkIiBWZXJzaW9uPSIyLjAiIElzc3VlSW5zdGFudD0iMjAyNi0xMS0wNFQxODo0OTowMFoiIERlc3RpbmF0aW9uPSJodHRwczovL2dhdGV3YXkuZGV2Lm9wZW5jb25leHQubG9jYWwvZ3NzcC9kZW1vX2dzc3AvY29uc3VtZS1hc3NlcnRpb24iIEluUmVzcG9uc2VUbz0iXzA4ZTQwODFkNzUyNTYyOTRlMzU5YjViNjA0ZTRmOTg2NjM2ZTYyMzcyZDQ1NWFhYTBkYmE0NTZhODE0YSI+PHNhbWw6SXNzdWVyPmh0dHBzOi8vZGVtb2dzc3AuZGV2Lm9wZW5jb25leHQubG9jYWwvc2FtbC9tZXRhZGF0YTwvc2FtbDpJc3N1ZXI+PHNhbWxwOlN0YXR1cz48c2FtbHA6U3RhdHVzQ29kZSBWYWx1ZT0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOnN0YXR1czpTdWNjZXNzIi8+PC9zYW1scDpTdGF0dXM+PHNhbWw6QXNzZXJ0aW9uIHhtbG5zOnhzaT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS9YTUxTY2hlbWEtaW5zdGFuY2UiIHhtbG5zOnhzPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxL1hNTFNjaGVtYSIgSUQ9Il80NTUxZTcxNmI4YWFmMzY1MTk5ZTJkYWU1YjZmZTdhYTYwZGZhM2JlMDg1NDIwMWNmYTk1NDdiY2UyY2QiIFZlcnNpb249IjIuMCIgSXNzdWVJbnN0YW50PSIyMDI2LTExLTA0VDE4OjQ5OjAwWiI+PHNhbWw6SXNzdWVyPmh0dHBzOi8vZGVtb2dzc3AuZGV2Lm9wZW5jb25leHQubG9jYWwvc2FtbC9tZXRhZGF0YTwvc2FtbDpJc3N1ZXI+PGRzOlNpZ25hdHVyZSB4bWxuczpkcz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnIyI+CiAgPGRzOlNpZ25lZEluZm8+PGRzOkNhbm9uaWNhbGl6YXRpb25NZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzEwL3htbC1leGMtYzE0biMiLz4KICAgIDxkczpTaWduYXR1cmVNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGRzaWctbW9yZSNyc2Etc2hhMjU2Ii8+CiAgPGRzOlJlZmVyZW5jZSBVUkk9IiNfNDU1MWU3MTZiOGFhZjM2NTE5OWUyZGFlNWI2ZmU3YWE2MGRmYTNiZTA4NTQyMDFjZmE5NTQ3YmNlMmNkIj48ZHM6VHJhbnNmb3Jtcz48ZHM6VHJhbnNmb3JtIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnI2VudmVsb3BlZC1zaWduYXR1cmUiLz48ZHM6VHJhbnNmb3JtIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS8xMC94bWwtZXhjLWMxNG4jIi8+PC9kczpUcmFuc2Zvcm1zPjxkczpEaWdlc3RNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGVuYyNzaGEyNTYiLz48ZHM6RGlnZXN0VmFsdWU+TTNjZlRPSHhMM2MzVEo5dkEweEVDa2YwTzBSYkdhdnFLVHVEVjdEdHNDcz08L2RzOkRpZ2VzdFZhbHVlPjwvZHM6UmVmZXJlbmNlPjwvZHM6U2lnbmVkSW5mbz48ZHM6U2lnbmF0dXJlVmFsdWU+c2p5WmdKc1RlcHkxZHhqUU1FZHM3blg2bFhYYytRR3RYUjFDOWcreSs4VUNoNzlLU1ExNXZFcVBzOWhjSTY2NDFFTXZUQlVBQnN3OXV6ejNOTFN3SHZkcnpKTFNmTGdsY05ZVG4xalNlMngxd2hXVndmQVhVazJuTG9DVUNnYVBIOW14bnBpUENYRXh4V3U5WTU0RHc3cTdsdi9yOUU4REZsSXlPRXJqcm44RmhZZzVReUlGVVBLZXQ5d0c1aVFONVREVTNXd3JxM2JGaUhCVk95TDVlYlRkYk84bnRSa2hLMlRiT3JDZDFkQWRpR3BXS29INDZiMFBYREhhdksvakxnbHBrYVlCSTRFbVE0VEttb0ZhR1V4SE9sQnU1RVBpRzRZVTE4MGFpRUZ0Ujh2RXNaRFFSeWpPY3FhN0hVbXYrNnU5SzRxcWlPalBZZUZOTFRFSGZwRkFjcEMrVGF6WERFTmFheHowN2hrczRIQ3VLVnRWNW1KSmxUTGR5dTl4RUR2OUFEQm85Y0VLcnlCVjlTM282Tm9YT3duMlg3UzBLNE96TDNpUTl0ZzQyalc2OExySVlOQXVXYmRITFlEQVJnendLUzFiMkNZOGh5NFZYRGhXbitFS21tNTZoYkUraDJFMXBqNng4VDFaVW10dkVld2hpcVdpZERYZWVFTkU8L2RzOlNpZ25hdHVyZVZhbHVlPgo8ZHM6S2V5SW5mbz48ZHM6WDUwOURhdGE+PGRzOlg1MDlDZXJ0aWZpY2F0ZT5NSUlFQ1RDQ0FuRUNGRjFvQVhrMmNRRnlQY0hTUVpXdzZ6Zmx3TkR2TUEwR0NTcUdTSWIzRFFFQkN3VUFNRUV4RmpBVUJnTlZCQU1NRFVSbGJXOGdSMU5UVUNCSlpGQXhKekFsQmdOVkJBb01Ia1JsZG1Wc2IzQnRaVzUwSUVSdlkydGxjaUJsYm5acGNtOXViV1Z1ZERBZUZ3MHlNekExTWpVd09UTXpNalJhRncweU9EQTFNak13T1RNek1qUmFNRUV4RmpBVUJnTlZCQU1NRFVSbGJXOGdSMU5UVUNCSlpGQXhKekFsQmdOVkJBb01Ia1JsZG1Wc2IzQnRaVzUwSUVSdlkydGxjaUJsYm5acGNtOXViV1Z1ZERDQ0FhSXdEUVlKS29aSWh2Y05BUUVCQlFBRGdnR1BBRENDQVlvQ2dnR0JBTFlreGsrK3RGVGtYN0hTVE1aMVFlL1AvcDlFclVUTmd4aXM4KzBNUmxNVTlSVkNvYW1FdEhERDJrUmhZdzIxbjN6TmZvbE1ud1hjdVRGQTJCOTV3cm5lZ2doTy8rUDliVUdQWWoxMUlha1BkS09QSW94eTVFMTFEZFZSNHVzVkFJYVJqcFNSdU96U0tBK3FoeTExbzVZWmgyM0tKZFJTR1ZaaWJ6WFI4cVdleHUrT0t3cUkwbC9ZaDFHbWJnNjA3OFZBK3VkNnV1UCtTVENOWXJJVHVzOUJBWlVqZy8vOWdPYTk2R3pMR3ZpckhLcGlYeDNlM2dxYzU0c1ZUeTNyQWVrVFFRdTNNRUVkaEEvalZlSTlTWGhuQkU3QU1jOGRyenJxd2JXN3hLOTA1K0gzSTNJT3NhQ0dEMVdkWnd3RlVWZFJROCtTV2xhNkZ2Y3UvK0QvOFBzeXVLRHpjOTJkejQweUtuN2NjdlNTVThDVzNQMVd3dkljQTlzYUVJaUhhazBXTWJDTjVNb2s2T0JmcllHdTFaU1QyUE4vZDRBUVFLQTVaams3NmlxRVplcFQ5TzJjdVNLQXZvT1pWU2xIckVxOVlwNytDT2VwUTBwcVljWC8rWU1kcWFlTTcxWEJUNlYrN1A1Q3VGNHBCTkE5MXBFNlc1cUtRMnVET0pFU3d3SURBUUFCTUEwR0NTcUdTSWIzRFFFQkN3VUFBNElCZ1FDZGx1eVdFUmJTRGxZM2JzMTZuQ2syM0JJa3RwMU1Uc1YzdmJXQnIzdzM1clBWa1E1WGVCOU9yU1RjZ1AyWGFGNkpOeDJlTUlhKzdOcGFiZTJtMEFBTXQvMnZWVEFtc2crL0twVmRQTFB6QTc1N0RTZDk5RWNvTW5KR1RHU1NMbzBGelYrb01Od3hXVjBSOWg3S2RoYnJHTUlMQWFIU0MrUyt6KzdkWXFJVEs1YkhjVEZtOXdrZHFBVkFxZ2UxSFQ3QS83QVpnTTNmU1hhQkhDMElQbzF2aUZuL2MrQzBja0QrSDB3b3FNSnkzNFF0c2d0dnZUelZKeGxiQUVjR2xJbTIrbVdkenppNTRIL09TWXArdFIrb0poaHZQZDc0akROYTQyUXBBUzdldXl6MkN5SS9iNWtpc1JVU1Q2TlIvOUpwMWl4MzZUWXdvSjJSSWp4RFpwaC9uZEdLKzFlYjdkUWQva2NnTi9qT3h5NDlZeHdUdG5oMGRhYktmMW5DcS90NkhtTVdHUFhCVVJhTU1COWhrNWI2djgrYVRHcGhqUUVyLzFuaWI1aGlXVTIzLytFTi9GNkxsSmhxS1FWU1JQOXFNMDVHWGJRbWZSVGFYdUFtajc2c2dHR3cvUVFlZTZ6dVh1UjlXUGFVbFhHZWdWZUg4aXFVYS9INnJoT2NUb0U9PC9kczpYNTA5Q2VydGlmaWNhdGU+PC9kczpYNTA5RGF0YT48L2RzOktleUluZm8+PC9kczpTaWduYXR1cmU+PHNhbWw6U3ViamVjdD48c2FtbDpOYW1lSUQgRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6bmFtZWlkLWZvcm1hdDpwZXJzaXN0ZW50Ij50ZXN0LXByZWZpeC02YWViN2RlNzUxOTY5NC4zNzc1NDY2NTwvc2FtbDpOYW1lSUQ+PHNhbWw6U3ViamVjdENvbmZpcm1hdGlvbiBNZXRob2Q9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDpjbTpiZWFyZXIiPjxzYW1sOlN1YmplY3RDb25maXJtYXRpb25EYXRhIE5vdE9uT3JBZnRlcj0iMjAyNi0xMS0wNFQxODo1NDowMFoiIFJlY2lwaWVudD0iaHR0cHM6Ly9nYXRld2F5LmRldi5vcGVuY29uZXh0LmxvY2FsL2dzc3AvZGVtb19nc3NwL2NvbnN1bWUtYXNzZXJ0aW9uIiBJblJlc3BvbnNlVG89Il8wOGU0MDgxZDc1MjU2Mjk0ZTM1OWI1YjYwNGU0Zjk4NjYzNmU2MjM3MmQ0NTVhYWEwZGJhNDU2YTgxNGEiLz48L3NhbWw6U3ViamVjdENvbmZpcm1hdGlvbj48L3NhbWw6U3ViamVjdD48c2FtbDpDb25kaXRpb25zIE5vdEJlZm9yZT0iMjAyNi0xMS0wNFQxODo0OTowMFoiIE5vdE9uT3JBZnRlcj0iMjAyNi0xMS0wNFQxODo1NDowMFoiPjxzYW1sOkF1ZGllbmNlUmVzdHJpY3Rpb24+PHNhbWw6QXVkaWVuY2U+aHR0cHM6Ly9nYXRld2F5LmRldi5vcGVuY29uZXh0LmxvY2FsL2dzc3AvZGVtb19nc3NwL21ldGFkYXRhPC9zYW1sOkF1ZGllbmNlPjwvc2FtbDpBdWRpZW5jZVJlc3RyaWN0aW9uPjwvc2FtbDpDb25kaXRpb25zPjxzYW1sOkF1dGhuU3RhdGVtZW50IEF1dGhuSW5zdGFudD0iMjAyNi0xMS0wNFQxODo0OTowMFoiPjxzYW1sOkF1dGhuQ29udGV4dD48c2FtbDpBdXRobkNvbnRleHRDbGFzc1JlZj51cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YWM6Y2xhc3NlczpNb2JpbGVUd29GYWN0b3JVbnJlZ2lzdGVyZWQ8L3NhbWw6QXV0aG5Db250ZXh0Q2xhc3NSZWY+PHNhbWw6QXV0aGVudGljYXRpbmdBdXRob3JpdHk+aHR0cHM6Ly9kZW1vZ3NzcC5kZXYub3BlbmNvbmV4dC5sb2NhbC9zYW1sL21ldGFkYXRhPC9zYW1sOkF1dGhlbnRpY2F0aW5nQXV0aG9yaXR5Pjwvc2FtbDpBdXRobkNvbnRleHQ+PC9zYW1sOkF1dGhuU3RhdGVtZW50Pjwvc2FtbDpBc3NlcnRpb24+PC9zYW1scDpSZXNwb25zZT4K';
