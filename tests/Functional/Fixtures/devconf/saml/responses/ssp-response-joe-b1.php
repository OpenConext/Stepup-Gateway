<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: response-post
 * Binding: HTTP-POST
 * Fixture name: ssp-response-joe-b1
 * Generated: 2026-11-04T17:57:12+00:00
 *
 * This is an HTTP-POST Response (base64-encoded XML).
 * Captured from: SAML Tracer or browser DevTools form data
 * Format: Base64-encoded XML value of SAMLResponse parameter
 *
 * Message Summary:
 *   - Binding: HTTP-POST (base64-encoded XML)
 *   - XML size: 7361 bytes
 *   - Signed: Yes (XML signature)
 *   - Signature Algorithm: http://www.w3.org/2001/04/xmldsig-more#rsa-sha256
 *   - Issuer: https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php
 *   - NameID: urn:collab:person:institution-b.example.com:joe-b1
 *   - AuthnContextClassRef: urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport
 *   - StatusCode: urn:oasis:names:tc:SAML:2.0:status:Success
 *   - Audience: https://gateway.dev.openconext.local/authentication/metadata
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_5f7f1454cdca64a60a09ed7b4b534ccce0a6ef4578" Version="2.0" IssueInstant="2026-11-04T17:51:46Z" Destination="https://gateway.dev.openconext.local/authentication/consume-assertion" InResponseTo="_cf9274ce9309abe0b087342c33857a292f5ad22dfe58243f8d8bf770772f">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="_b926dcb5b0bb3659388e47c4c825063461ff66d9a5" Version="2.0" IssueInstant="2026-11-04T17:51:46Z">
    <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#_b926dcb5b0bb3659388e47c4c825063461ff66d9a5">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>Khf3pZuuNGLCHdFCDzYehK8rPib9Fjoi38ccpuh/Lxg=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>NrsNA40JA9LXi1vrqwzqqFl4hZMhVB8UcSUzMT8j28DwJEqlealtMxd0F8c7eI0gzssKsfHjIGQqRYxxmGiM8dWV++ptQJo1+UT+iy3jI0a4O3851gO4jMOQI537Z7xYKAaDK/92gwo/HWpK0R0GWZEcGTxEqwmlHj20JfMVNxoGboS8aeak6nHVKKKH3oHEx0yXhx+hB+793v3foyicX2VxLrtMBu76pb17/3YRQB0PMegwjaKuvOp9p2uKVfK3B/jdy8MfcIcfHfPGflASlhtyLDMb2qvvDhRyKUtFyRgPmDA5ruNhqAFnf+eW5bQbKbUHSmdKEUXl93sApjGAIM8VC1vbe09LcRQupPRcsRBAvgwTGYz4UQ30NL9ln6BZlHgoj1M9Npi9EUaB34vJBHHyE0i34O0JRjFt92ABRU3UpVmti+/M9hmRv3qV7yU8H7VE7/tbzvB6Qr5EzbcgoAWVN6qaQhJ0J1aRZNwxt9FJQlS1oJfVCyLcVG+UYTB2</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MIIECzCCAnMCFB5yH62OMDs5L8unoJeyJOBK3rQ9MA0GCSqGSIb3DQEBCwUAMEIxFzAVBgNVBAMMDnNpbXBsZXNhbWwgSWRQMScwJQYDVQQKDB5EZXZlbG9wbWVudCBEb2NrZXIgZW52aXJvbm1lbnQwHhcNMjMwNTI1MDkzMzE1WhcNMjgwNTIzMDkzMzE1WjBCMRcwFQYDVQQDDA5zaW1wbGVzYW1sIElkUDEnMCUGA1UECgweRGV2ZWxvcG1lbnQgRG9ja2VyIGVudmlyb25tZW50MIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAwT9bwWfEPAHHHFmKmH3MBbIAdg10gDGqyOB5bV73KY/UZG53Wqx30RNz2tuvP2HGn1VW3hCK0NM0nbZbrIYAuvwfR7QZHW98/GrYMYtZWAfqb2XxWX/uirQjXQNtxtp3gEN7u23qZdsKn6JE1DmFXx7lfcG367+IXrYdO5ySIeTZ7VXAGeXgeaubFAQjOLqpolcIM/DTyD4Tfj3vWQaHcxVvrpMs/MCZMOGPhaz29truOEAfbVaGubu+dBLtMe7MH/eogUmeOU5Xw6MFxnkWBeByzk8bOJ5bmRN6Pe4YcHjmydylMHqnNLd6I2iBx+GDnwwV3sT9E8XzYVHuC288cPHgoSiKtbEcuflBs9zn1sMMXW7CZUlEWYAxBN/fG+UDGBEx0NTjEowrYPUfP4BzHQSTkv/U2XDTTB2pmc14Vj1Jmh68+dYLuFJ+Dayet8gjC6XwjdPnQdNN9wXjPKz938qSrkPsaf0G3rNhWuHnk7ndahOLiibnqskLz2F/SXhXAgMBAAEwDQYJKoZIhvcNAQELBQADggGBALEvo9DKooOTun0TcSjT4ziumwvkDeR8yIDuHNqsE4bNJKp/oxiSDRDQyA1AT7qPbTabcgOw0wxHGB1iFY2urmXKcu2ryjsxnN7M13fUE6n6Z2Z+9u3ZuHB4hBAb1WNdjStrGexyReTkOeFtTB3XjZXxMf/FrQ7b84UPb/zUyuz+khSFUAN03Zdf7PEbGav3SFdsaLQoCtDWrJTduG3JDnoG3X+kWY8hIYuQhmzqYV1LxJDX9SBPYC2bgiM8AfnzQVxdV9pQqiuaiM9L/9wP1ZOBjudDrguOPB2mo4/wqEKdJQsVnsXp5GJvS28fI3GysuaKZbcqwwObdw8xzQnil8tUZalx2PUnTiDPNv7yoACRIc4jNFLiU+fNULaU4KbT3XjfmAzJUftHG+O60522lqVC67kA+vOwIJbz/SiikBnkmT5kr4dftbjt3YT22kVmaV0BBwMg0cvJMHquo8qjg1LwaDmGj7CAqR+OpFUGnH1CFcdT0V2svQX2XKkI5O8O9g==</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:institution-b.example.com:joe-b1</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData NotOnOrAfter="2026-11-04T17:56:46Z" Recipient="https://gateway.dev.openconext.local/authentication/consume-assertion" InResponseTo="_cf9274ce9309abe0b087342c33857a292f5ad22dfe58243f8d8bf770772f"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2026-11-04T17:51:16Z" NotOnOrAfter="2026-11-04T17:56:46Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://gateway.dev.openconext.local/authentication/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement AuthnInstant="2026-11-04T17:51:46Z" SessionNotOnOrAfter="2026-11-05T01:51:46Z" SessionIndex="_dfb1deda45f298ddc93149ae2ed3e1dff90b962a8a">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef>
      </saml:AuthnContext>
    </saml:AuthnStatement>
    <saml:AttributeStatement>
      <saml:Attribute Name="urn:mace:surf.nl:attribute-def:internal-collabPersonId" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">urn:collab:person:institution-b.example.com:joe-b1</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:uid" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">joe-b1</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:eduPersonPrincipalName" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">joe-b1@institution-b.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:givenName" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">gn-joe-b1</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:sn" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">sn-institution-b.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:cn" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">joe-b1 institution-b.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:mail" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">joe+joe-b1@dev.openconext.local</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:displayName" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">d-joe-b1 institution-b.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:eduPersonAffiliation" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">student</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:terena.org:attribute-def:schacHomeOrganization" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">institution-b.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:terena.org:attribute-def:schacHomeOrganizationType" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">urn:mace:terena.org:schac:homeOrganizationType:int:university</saml:AttributeValue>
      </saml:Attribute>
    </saml:AttributeStatement>
  </saml:Assertion>
</samlp:Response>
*/

return 'PHNhbWxwOlJlc3BvbnNlIHhtbG5zOnNhbWxwPSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6cHJvdG9jb2wiIHhtbG5zOnNhbWw9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphc3NlcnRpb24iIElEPSJfNWY3ZjE0NTRjZGNhNjRhNjBhMDllZDdiNGI1MzRjY2NlMGE2ZWY0NTc4IiBWZXJzaW9uPSIyLjAiIElzc3VlSW5zdGFudD0iMjAyNi0xMS0wNFQxNzo1MTo0NloiIERlc3RpbmF0aW9uPSJodHRwczovL2dhdGV3YXkuZGV2Lm9wZW5jb25leHQubG9jYWwvYXV0aGVudGljYXRpb24vY29uc3VtZS1hc3NlcnRpb24iIEluUmVzcG9uc2VUbz0iX2NmOTI3NGNlOTMwOWFiZTBiMDg3MzQyYzMzODU3YTI5MmY1YWQyMmRmZTU4MjQzZjhkOGJmNzcwNzcyZiI+PHNhbWw6SXNzdWVyPmh0dHBzOi8vc3NwLmRldi5vcGVuY29uZXh0LmxvY2FsL3NpbXBsZXNhbWwvc2FtbDIvaWRwL21ldGFkYXRhLnBocDwvc2FtbDpJc3N1ZXI+PHNhbWxwOlN0YXR1cz48c2FtbHA6U3RhdHVzQ29kZSBWYWx1ZT0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOnN0YXR1czpTdWNjZXNzIi8+PC9zYW1scDpTdGF0dXM+PHNhbWw6QXNzZXJ0aW9uIHhtbG5zOnhzaT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS9YTUxTY2hlbWEtaW5zdGFuY2UiIHhtbG5zOnhzPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxL1hNTFNjaGVtYSIgSUQ9Il9iOTI2ZGNiNWIwYmIzNjU5Mzg4ZTQ3YzRjODI1MDYzNDYxZmY2NmQ5YTUiIFZlcnNpb249IjIuMCIgSXNzdWVJbnN0YW50PSIyMDI2LTExLTA0VDE3OjUxOjQ2WiI+PHNhbWw6SXNzdWVyPmh0dHBzOi8vc3NwLmRldi5vcGVuY29uZXh0LmxvY2FsL3NpbXBsZXNhbWwvc2FtbDIvaWRwL21ldGFkYXRhLnBocDwvc2FtbDpJc3N1ZXI+PGRzOlNpZ25hdHVyZSB4bWxuczpkcz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnIyI+CiAgPGRzOlNpZ25lZEluZm8+PGRzOkNhbm9uaWNhbGl6YXRpb25NZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzEwL3htbC1leGMtYzE0biMiLz4KICAgIDxkczpTaWduYXR1cmVNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGRzaWctbW9yZSNyc2Etc2hhMjU2Ii8+CiAgPGRzOlJlZmVyZW5jZSBVUkk9IiNfYjkyNmRjYjViMGJiMzY1OTM4OGU0N2M0YzgyNTA2MzQ2MWZmNjZkOWE1Ij48ZHM6VHJhbnNmb3Jtcz48ZHM6VHJhbnNmb3JtIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnI2VudmVsb3BlZC1zaWduYXR1cmUiLz48ZHM6VHJhbnNmb3JtIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS8xMC94bWwtZXhjLWMxNG4jIi8+PC9kczpUcmFuc2Zvcm1zPjxkczpEaWdlc3RNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGVuYyNzaGEyNTYiLz48ZHM6RGlnZXN0VmFsdWU+S2hmM3BadXVOR0xDSGRGQ0R6WWVoSzhyUGliOUZqb2kzOGNjcHVoL0x4Zz08L2RzOkRpZ2VzdFZhbHVlPjwvZHM6UmVmZXJlbmNlPjwvZHM6U2lnbmVkSW5mbz48ZHM6U2lnbmF0dXJlVmFsdWU+TnJzTkE0MEpBOUxYaTF2cnF3enFxRmw0aFpNaFZCOFVjU1V6TVQ4ajI4RHdKRXFsZWFsdE14ZDBGOGM3ZUkwZ3pzc0tzZkhqSUdRcVJZeHhtR2lNOGRXVisrcHRRSm8xK1VUK2l5M2pJMGE0TzM4NTFnTzRqTU9RSTUzN1o3eFlLQWFESy85Mmd3by9IV3BLMFIwR1daRWNHVHhFcXdtbEhqMjBKZk1WTnhvR2JvUzhhZWFrNm5IVktLS0gzb0hFeDB5WGh4K2hCKzc5M3YzZm95aWNYMlZ4THJ0TUJ1NzZwYjE3LzNZUlFCMFBNZWd3amFLdXZPcDlwMnVLVmZLM0IvamR5OE1mY0ljZkhmUEdmbEFTbGh0eUxETWIycXZ2RGhSeUtVdEZ5UmdQbURBNXJ1TmhxQUZuZitlVzViUWJLYlVIU21kS0VVWGw5M3NBcGpHQUlNOFZDMXZiZTA5TGNSUXVwUFJjc1JCQXZnd1RHWXo0VVEzME5MOWxuNkJabEhnb2oxTTlOcGk5RVVhQjM0dkpCSEh5RTBpMzRPMEpSakZ0OTJBQlJVM1VwVm10aSsvTTlobVJ2M3FWN3lVOEg3VkU3L3RienZCNlFyNUV6YmNnb0FXVk42cWFRaEowSjFhUlpOd3h0OUZKUWxTMW9KZlZDeUxjVkcrVVlUQjI8L2RzOlNpZ25hdHVyZVZhbHVlPgo8ZHM6S2V5SW5mbz48ZHM6WDUwOURhdGE+PGRzOlg1MDlDZXJ0aWZpY2F0ZT5NSUlFQ3pDQ0FuTUNGQjV5SDYyT01EczVMOHVub0pleUpPQkszclE5TUEwR0NTcUdTSWIzRFFFQkN3VUFNRUl4RnpBVkJnTlZCQU1NRG5OcGJYQnNaWE5oYld3Z1NXUlFNU2N3SlFZRFZRUUtEQjVFWlhabGJHOXdiV1Z1ZENCRWIyTnJaWElnWlc1MmFYSnZibTFsYm5Rd0hoY05Nak13TlRJMU1Ea3pNekUxV2hjTk1qZ3dOVEl6TURrek16RTFXakJDTVJjd0ZRWURWUVFEREE1emFXMXdiR1Z6WVcxc0lFbGtVREVuTUNVR0ExVUVDZ3dlUkdWMlpXeHZjRzFsYm5RZ1JHOWphMlZ5SUdWdWRtbHliMjV0Wlc1ME1JSUJvakFOQmdrcWhraUc5dzBCQVFFRkFBT0NBWThBTUlJQmlnS0NBWUVBd1Q5YndXZkVQQUhISEZtS21IM01CYklBZGcxMGdER3F5T0I1YlY3M0tZL1VaRzUzV3F4MzBSTnoydHV2UDJIR24xVlczaENLME5NMG5iWmJySVlBdXZ3ZlI3UVpIVzk4L0dyWU1ZdFpXQWZxYjJYeFdYL3VpclFqWFFOdHh0cDNnRU43dTIzcVpkc0tuNkpFMURtRlh4N2xmY0czNjcrSVhyWWRPNXlTSWVUWjdWWEFHZVhnZWF1YkZBUWpPTHFwb2xjSU0vRFR5RDRUZmozdldRYUhjeFZ2cnBNcy9NQ1pNT0dQaGF6Mjl0cnVPRUFmYlZhR3VidStkQkx0TWU3TUgvZW9nVW1lT1U1WHc2TUZ4bmtXQmVCeXprOGJPSjVibVJONlBlNFljSGpteWR5bE1IcW5OTGQ2STJpQngrR0Rud3dWM3NUOUU4WHpZVkh1QzI4OGNQSGdvU2lLdGJFY3VmbEJzOXpuMXNNTVhXN0NaVWxFV1lBeEJOL2ZHK1VER0JFeDBOVGpFb3dyWVBVZlA0QnpIUVNUa3YvVTJYRFRUQjJwbWMxNFZqMUptaDY4K2RZTHVGSitEYXlldDhnakM2WHdqZFBuUWROTjl3WGpQS3o5MzhxU3JrUHNhZjBHM3JOaFd1SG5rN25kYWhPTGlpYm5xc2tMejJGL1NYaFhBZ01CQUFFd0RRWUpLb1pJaHZjTkFRRUxCUUFEZ2dHQkFMRXZvOURLb29PVHVuMFRjU2pUNHppdW13dmtEZVI4eUlEdUhOcXNFNGJOSktwL294aVNEUkRReUExQVQ3cVBiVGFiY2dPdzB3eEhHQjFpRlkydXJtWEtjdTJyeWpzeG5ON00xM2ZVRTZuNloyWis5dTNadUhCNGhCQWIxV05kalN0ckdleHlSZVRrT2VGdFRCM1hqWlh4TWYvRnJRN2I4NFVQYi96VXl1eitraFNGVUFOMDNaZGY3UEViR2F2M1NGZHNhTFFvQ3REV3JKVGR1RzNKRG5vRzNYK2tXWThoSVl1UWhtenFZVjFMeEpEWDlTQlBZQzJiZ2lNOEFmbnpRVnhkVjlwUXFpdWFpTTlMLzl3UDFaT0JqdWREcmd1T1BCMm1vNC93cUVLZEpRc1Zuc1hwNUdKdlMyOGZJM0d5c3VhS1piY3F3d09iZHc4eHpRbmlsOHRVWmFseDJQVW5UaURQTnY3eW9BQ1JJYzRqTkZMaVUrZk5VTGFVNEtiVDNYamZtQXpKVWZ0SEcrTzYwNTIybHFWQzY3a0Erdk93SUpiei9TaWlrQm5rbVQ1a3I0ZGZ0Ymp0M1lUMjJrVm1hVjBCQndNZzBjdkpNSHF1bzhxamcxTHdhRG1HajdDQXFSK09wRlVHbkgxQ0ZjZFQwVjJzdlFYMlhLa0k1TzhPOWc9PTwvZHM6WDUwOUNlcnRpZmljYXRlPjwvZHM6WDUwOURhdGE+PC9kczpLZXlJbmZvPjwvZHM6U2lnbmF0dXJlPjxzYW1sOlN1YmplY3Q+PHNhbWw6TmFtZUlEIEZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6MS4xOm5hbWVpZC1mb3JtYXQ6dW5zcGVjaWZpZWQiPnVybjpjb2xsYWI6cGVyc29uOmluc3RpdHV0aW9uLWIuZXhhbXBsZS5jb206am9lLWIxPC9zYW1sOk5hbWVJRD48c2FtbDpTdWJqZWN0Q29uZmlybWF0aW9uIE1ldGhvZD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmNtOmJlYXJlciI+PHNhbWw6U3ViamVjdENvbmZpcm1hdGlvbkRhdGEgTm90T25PckFmdGVyPSIyMDI2LTExLTA0VDE3OjU2OjQ2WiIgUmVjaXBpZW50PSJodHRwczovL2dhdGV3YXkuZGV2Lm9wZW5jb25leHQubG9jYWwvYXV0aGVudGljYXRpb24vY29uc3VtZS1hc3NlcnRpb24iIEluUmVzcG9uc2VUbz0iX2NmOTI3NGNlOTMwOWFiZTBiMDg3MzQyYzMzODU3YTI5MmY1YWQyMmRmZTU4MjQzZjhkOGJmNzcwNzcyZiIvPjwvc2FtbDpTdWJqZWN0Q29uZmlybWF0aW9uPjwvc2FtbDpTdWJqZWN0PjxzYW1sOkNvbmRpdGlvbnMgTm90QmVmb3JlPSIyMDI2LTExLTA0VDE3OjUxOjE2WiIgTm90T25PckFmdGVyPSIyMDI2LTExLTA0VDE3OjU2OjQ2WiI+PHNhbWw6QXVkaWVuY2VSZXN0cmljdGlvbj48c2FtbDpBdWRpZW5jZT5odHRwczovL2dhdGV3YXkuZGV2Lm9wZW5jb25leHQubG9jYWwvYXV0aGVudGljYXRpb24vbWV0YWRhdGE8L3NhbWw6QXVkaWVuY2U+PC9zYW1sOkF1ZGllbmNlUmVzdHJpY3Rpb24+PC9zYW1sOkNvbmRpdGlvbnM+PHNhbWw6QXV0aG5TdGF0ZW1lbnQgQXV0aG5JbnN0YW50PSIyMDI2LTExLTA0VDE3OjUxOjQ2WiIgU2Vzc2lvbk5vdE9uT3JBZnRlcj0iMjAyNi0xMS0wNVQwMTo1MTo0NloiIFNlc3Npb25JbmRleD0iX2RmYjFkZWRhNDVmMjk4ZGRjOTMxNDlhZTJlZDNlMWRmZjkwYjk2MmE4YSI+PHNhbWw6QXV0aG5Db250ZXh0PjxzYW1sOkF1dGhuQ29udGV4dENsYXNzUmVmPnVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphYzpjbGFzc2VzOlBhc3N3b3JkUHJvdGVjdGVkVHJhbnNwb3J0PC9zYW1sOkF1dGhuQ29udGV4dENsYXNzUmVmPjwvc2FtbDpBdXRobkNvbnRleHQ+PC9zYW1sOkF1dGhuU3RhdGVtZW50PjxzYW1sOkF0dHJpYnV0ZVN0YXRlbWVudD48c2FtbDpBdHRyaWJ1dGUgTmFtZT0idXJuOm1hY2U6c3VyZi5ubDphdHRyaWJ1dGUtZGVmOmludGVybmFsLWNvbGxhYlBlcnNvbklkIiBOYW1lRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YXR0cm5hbWUtZm9ybWF0OnVyaSI+PHNhbWw6QXR0cmlidXRlVmFsdWUgeHNpOnR5cGU9InhzOnN0cmluZyI+dXJuOmNvbGxhYjpwZXJzb246aW5zdGl0dXRpb24tYi5leGFtcGxlLmNvbTpqb2UtYjE8L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgTmFtZT0idXJuOm1hY2U6ZGlyOmF0dHJpYnV0ZS1kZWY6dWlkIiBOYW1lRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YXR0cm5hbWUtZm9ybWF0OnVyaSI+PHNhbWw6QXR0cmlidXRlVmFsdWUgeHNpOnR5cGU9InhzOnN0cmluZyI+am9lLWIxPC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PHNhbWw6QXR0cmlidXRlIE5hbWU9InVybjptYWNlOmRpcjphdHRyaWJ1dGUtZGVmOmVkdVBlcnNvblByaW5jaXBhbE5hbWUiIE5hbWVGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48c2FtbDpBdHRyaWJ1dGVWYWx1ZSB4c2k6dHlwZT0ieHM6c3RyaW5nIj5qb2UtYjFAaW5zdGl0dXRpb24tYi5leGFtcGxlLmNvbTwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTpkaXI6YXR0cmlidXRlLWRlZjpnaXZlbk5hbWUiIE5hbWVGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48c2FtbDpBdHRyaWJ1dGVWYWx1ZSB4c2k6dHlwZT0ieHM6c3RyaW5nIj5nbi1qb2UtYjE8L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgTmFtZT0idXJuOm1hY2U6ZGlyOmF0dHJpYnV0ZS1kZWY6c24iIE5hbWVGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48c2FtbDpBdHRyaWJ1dGVWYWx1ZSB4c2k6dHlwZT0ieHM6c3RyaW5nIj5zbi1pbnN0aXR1dGlvbi1iLmV4YW1wbGUuY29tPC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PHNhbWw6QXR0cmlidXRlIE5hbWU9InVybjptYWNlOmRpcjphdHRyaWJ1dGUtZGVmOmNuIiBOYW1lRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YXR0cm5hbWUtZm9ybWF0OnVyaSI+PHNhbWw6QXR0cmlidXRlVmFsdWUgeHNpOnR5cGU9InhzOnN0cmluZyI+am9lLWIxIGluc3RpdHV0aW9uLWIuZXhhbXBsZS5jb208L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgTmFtZT0idXJuOm1hY2U6ZGlyOmF0dHJpYnV0ZS1kZWY6bWFpbCIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPmpvZStqb2UtYjFAZGV2Lm9wZW5jb25leHQubG9jYWw8L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgTmFtZT0idXJuOm1hY2U6ZGlyOmF0dHJpYnV0ZS1kZWY6ZGlzcGxheU5hbWUiIE5hbWVGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48c2FtbDpBdHRyaWJ1dGVWYWx1ZSB4c2k6dHlwZT0ieHM6c3RyaW5nIj5kLWpvZS1iMSBpbnN0aXR1dGlvbi1iLmV4YW1wbGUuY29tPC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PHNhbWw6QXR0cmlidXRlIE5hbWU9InVybjptYWNlOmRpcjphdHRyaWJ1dGUtZGVmOmVkdVBlcnNvbkFmZmlsaWF0aW9uIiBOYW1lRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YXR0cm5hbWUtZm9ybWF0OnVyaSI+PHNhbWw6QXR0cmlidXRlVmFsdWUgeHNpOnR5cGU9InhzOnN0cmluZyI+c3R1ZGVudDwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTp0ZXJlbmEub3JnOmF0dHJpYnV0ZS1kZWY6c2NoYWNIb21lT3JnYW5pemF0aW9uIiBOYW1lRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YXR0cm5hbWUtZm9ybWF0OnVyaSI+PHNhbWw6QXR0cmlidXRlVmFsdWUgeHNpOnR5cGU9InhzOnN0cmluZyI+aW5zdGl0dXRpb24tYi5leGFtcGxlLmNvbTwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTp0ZXJlbmEub3JnOmF0dHJpYnV0ZS1kZWY6c2NoYWNIb21lT3JnYW5pemF0aW9uVHlwZSIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPnVybjptYWNlOnRlcmVuYS5vcmc6c2NoYWM6aG9tZU9yZ2FuaXphdGlvblR5cGU6aW50OnVuaXZlcnNpdHk8L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48L3NhbWw6QXR0cmlidXRlU3RhdGVtZW50Pjwvc2FtbDpBc3NlcnRpb24+PC9zYW1scDpSZXNwb25zZT4=';
