<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: response-post
 * Binding: HTTP-POST
 * Fixture name: ssp-response-jane-a1
 * Generated: 2026-10-27T10:42:20+00:00
 *
 * This is an HTTP-POST Response (base64-encoded XML).
 * Captured from: SAML Tracer or browser DevTools form data
 * Format: Base64-encoded XML value of SAMLResponse parameter
 * Note: HTTP-Redirect Response does not exist - responses always use HTTP-POST
 *
 * Message Summary:
 *   - Binding: HTTP-POST (base64-encoded XML)
 *   - XML size: 7370 bytes
 *   - Signed: Yes (XML signature)
 *   - Signature Algorithm: http://www.w3.org/2001/04/xmldsig-more#rsa-sha256
 *   - Issuer: https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php
 *   - NameID: urn:collab:person:institution-a.example.com:jane-a1
 *   - AuthnContextClassRef: urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport
 *   - StatusCode: urn:oasis:names:tc:SAML:2.0:status:Success
 *   - Audience: https://gateway.dev.openconext.local/authentication/metadata
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_3c5828ec8072fd15973938eb5876d1df5657d673fd" Version="2.0" IssueInstant="2026-10-27T10:40:57Z" Destination="https://gateway.dev.openconext.local/authentication/consume-assertion" InResponseTo="_d8b47fe679a2e20dbe1559100fe13e8781205a94a9f477e9b0ff4e4ed4a3">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="_efda28ce5a3ecd7f3e4e0ec129d1e62d66a8c23100" Version="2.0" IssueInstant="2026-10-27T10:40:57Z">
    <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#_efda28ce5a3ecd7f3e4e0ec129d1e62d66a8c23100">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>J4kJVLJV2x+Cl55bT+fWiu+bnj7BNJ+xr3VbAp/A0kA=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>LG7rktxG7SWd73Lfolbn6L73ELdS8+kPICuiphEeiNpF15whyUeltps3ijYfLciK54ffRoRSCPeeaf/1FtOXXEMWvEYhMaQ0tx6+uFwCQPeRz+UVr/dx2og5+k+EbD5+om+BioId+cdxX9ocUNLvenb+GagkfFZ044zBPbLcP9GijI6XoY/69Q+l/Hzik4dSLJYVPS+Mq3H3f/0za7F9kSJVss37bTEyVHWLj9eJk3r0F9x7yARVFi5jbUZjpi/H/7e7xTKGw+IOXVwJ2bLzKksY7TQ5DGiTYGnKMLFTku58wd+j94iYQuZlFHe0Y8W/jpQypgEMJIqpr18dIP3+SR9nLbQ3AQcAq8c4N0/qxH7650Tw0w82QDny//aSojCHJcGmQ0JKyc8dlYCXFVVPpvTygmXRtUdFPQUCBmVdAzjF8bL8z4YEu270jFDeEN6AQ8viSFh8cZJUAqF3b4W9+84x7TvSsb4ONkjDIGD7lrYth++1WwS8Qm9ego7SdQIm</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MIIECzCCAnMCFB5yH62OMDs5L8unoJeyJOBK3rQ9MA0GCSqGSIb3DQEBCwUAMEIxFzAVBgNVBAMMDnNpbXBsZXNhbWwgSWRQMScwJQYDVQQKDB5EZXZlbG9wbWVudCBEb2NrZXIgZW52aXJvbm1lbnQwHhcNMjMwNTI1MDkzMzE1WhcNMjgwNTIzMDkzMzE1WjBCMRcwFQYDVQQDDA5zaW1wbGVzYW1sIElkUDEnMCUGA1UECgweRGV2ZWxvcG1lbnQgRG9ja2VyIGVudmlyb25tZW50MIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAwT9bwWfEPAHHHFmKmH3MBbIAdg10gDGqyOB5bV73KY/UZG53Wqx30RNz2tuvP2HGn1VW3hCK0NM0nbZbrIYAuvwfR7QZHW98/GrYMYtZWAfqb2XxWX/uirQjXQNtxtp3gEN7u23qZdsKn6JE1DmFXx7lfcG367+IXrYdO5ySIeTZ7VXAGeXgeaubFAQjOLqpolcIM/DTyD4Tfj3vWQaHcxVvrpMs/MCZMOGPhaz29truOEAfbVaGubu+dBLtMe7MH/eogUmeOU5Xw6MFxnkWBeByzk8bOJ5bmRN6Pe4YcHjmydylMHqnNLd6I2iBx+GDnwwV3sT9E8XzYVHuC288cPHgoSiKtbEcuflBs9zn1sMMXW7CZUlEWYAxBN/fG+UDGBEx0NTjEowrYPUfP4BzHQSTkv/U2XDTTB2pmc14Vj1Jmh68+dYLuFJ+Dayet8gjC6XwjdPnQdNN9wXjPKz938qSrkPsaf0G3rNhWuHnk7ndahOLiibnqskLz2F/SXhXAgMBAAEwDQYJKoZIhvcNAQELBQADggGBALEvo9DKooOTun0TcSjT4ziumwvkDeR8yIDuHNqsE4bNJKp/oxiSDRDQyA1AT7qPbTabcgOw0wxHGB1iFY2urmXKcu2ryjsxnN7M13fUE6n6Z2Z+9u3ZuHB4hBAb1WNdjStrGexyReTkOeFtTB3XjZXxMf/FrQ7b84UPb/zUyuz+khSFUAN03Zdf7PEbGav3SFdsaLQoCtDWrJTduG3JDnoG3X+kWY8hIYuQhmzqYV1LxJDX9SBPYC2bgiM8AfnzQVxdV9pQqiuaiM9L/9wP1ZOBjudDrguOPB2mo4/wqEKdJQsVnsXp5GJvS28fI3GysuaKZbcqwwObdw8xzQnil8tUZalx2PUnTiDPNv7yoACRIc4jNFLiU+fNULaU4KbT3XjfmAzJUftHG+O60522lqVC67kA+vOwIJbz/SiikBnkmT5kr4dftbjt3YT22kVmaV0BBwMg0cvJMHquo8qjg1LwaDmGj7CAqR+OpFUGnH1CFcdT0V2svQX2XKkI5O8O9g==</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:institution-a.example.com:jane-a1</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData NotOnOrAfter="2026-10-27T10:45:57Z" Recipient="https://gateway.dev.openconext.local/authentication/consume-assertion" InResponseTo="_d8b47fe679a2e20dbe1559100fe13e8781205a94a9f477e9b0ff4e4ed4a3"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2026-10-27T10:40:27Z" NotOnOrAfter="2026-10-27T10:45:57Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://gateway.dev.openconext.local/authentication/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement AuthnInstant="2026-10-27T10:40:57Z" SessionNotOnOrAfter="2026-10-27T18:40:57Z" SessionIndex="_b94e9c52674429f9410731b2264c0e6891ef4ddcf1">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef>
      </saml:AuthnContext>
    </saml:AuthnStatement>
    <saml:AttributeStatement>
      <saml:Attribute Name="urn:mace:surf.nl:attribute-def:internal-collabPersonId" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">urn:collab:person:institution-a.example.com:jane-a1</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:uid" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">jane-a1</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:eduPersonPrincipalName" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">jane-a1@institution-a.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:givenName" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">gn-jane-a1</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:sn" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">sn-institution-a.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:cn" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">jane-a1 institution-a.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:mail" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">jane+jane-a1@dev.openconext.local</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:displayName" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">d-jane-a1 institution-a.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:eduPersonAffiliation" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">student</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:terena.org:attribute-def:schacHomeOrganization" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">institution-a.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:terena.org:attribute-def:schacHomeOrganizationType" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">urn:mace:terena.org:schac:homeOrganizationType:int:university</saml:AttributeValue>
      </saml:Attribute>
    </saml:AttributeStatement>
  </saml:Assertion>
</samlp:Response>
*/

return 'PHNhbWxwOlJlc3BvbnNlIHhtbG5zOnNhbWxwPSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6cHJvdG9jb2wiIHhtbG5zOnNhbWw9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphc3NlcnRpb24iIElEPSJfM2M1ODI4ZWM4MDcyZmQxNTk3MzkzOGViNTg3NmQxZGY1NjU3ZDY3M2ZkIiBWZXJzaW9uPSIyLjAiIElzc3VlSW5zdGFudD0iMjAyNi0xMC0yN1QxMDo0MDo1N1oiIERlc3RpbmF0aW9uPSJodHRwczovL2dhdGV3YXkuZGV2Lm9wZW5jb25leHQubG9jYWwvYXV0aGVudGljYXRpb24vY29uc3VtZS1hc3NlcnRpb24iIEluUmVzcG9uc2VUbz0iX2Q4YjQ3ZmU2NzlhMmUyMGRiZTE1NTkxMDBmZTEzZTg3ODEyMDVhOTRhOWY0NzdlOWIwZmY0ZTRlZDRhMyI+PHNhbWw6SXNzdWVyPmh0dHBzOi8vc3NwLmRldi5vcGVuY29uZXh0LmxvY2FsL3NpbXBsZXNhbWwvc2FtbDIvaWRwL21ldGFkYXRhLnBocDwvc2FtbDpJc3N1ZXI+PHNhbWxwOlN0YXR1cz48c2FtbHA6U3RhdHVzQ29kZSBWYWx1ZT0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOnN0YXR1czpTdWNjZXNzIi8+PC9zYW1scDpTdGF0dXM+PHNhbWw6QXNzZXJ0aW9uIHhtbG5zOnhzaT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS9YTUxTY2hlbWEtaW5zdGFuY2UiIHhtbG5zOnhzPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxL1hNTFNjaGVtYSIgSUQ9Il9lZmRhMjhjZTVhM2VjZDdmM2U0ZTBlYzEyOWQxZTYyZDY2YThjMjMxMDAiIFZlcnNpb249IjIuMCIgSXNzdWVJbnN0YW50PSIyMDI2LTEwLTI3VDEwOjQwOjU3WiI+PHNhbWw6SXNzdWVyPmh0dHBzOi8vc3NwLmRldi5vcGVuY29uZXh0LmxvY2FsL3NpbXBsZXNhbWwvc2FtbDIvaWRwL21ldGFkYXRhLnBocDwvc2FtbDpJc3N1ZXI+PGRzOlNpZ25hdHVyZSB4bWxuczpkcz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnIyI+CiAgPGRzOlNpZ25lZEluZm8+PGRzOkNhbm9uaWNhbGl6YXRpb25NZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzEwL3htbC1leGMtYzE0biMiLz4KICAgIDxkczpTaWduYXR1cmVNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGRzaWctbW9yZSNyc2Etc2hhMjU2Ii8+CiAgPGRzOlJlZmVyZW5jZSBVUkk9IiNfZWZkYTI4Y2U1YTNlY2Q3ZjNlNGUwZWMxMjlkMWU2MmQ2NmE4YzIzMTAwIj48ZHM6VHJhbnNmb3Jtcz48ZHM6VHJhbnNmb3JtIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnI2VudmVsb3BlZC1zaWduYXR1cmUiLz48ZHM6VHJhbnNmb3JtIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS8xMC94bWwtZXhjLWMxNG4jIi8+PC9kczpUcmFuc2Zvcm1zPjxkczpEaWdlc3RNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGVuYyNzaGEyNTYiLz48ZHM6RGlnZXN0VmFsdWU+SjRrSlZMSlYyeCtDbDU1YlQrZldpdStibmo3Qk5KK3hyM1ZiQXAvQTBrQT08L2RzOkRpZ2VzdFZhbHVlPjwvZHM6UmVmZXJlbmNlPjwvZHM6U2lnbmVkSW5mbz48ZHM6U2lnbmF0dXJlVmFsdWU+TEc3cmt0eEc3U1dkNzNMZm9sYm42TDczRUxkUzgra1BJQ3VpcGhFZWlOcEYxNXdoeVVlbHRwczNpallmTGNpSzU0ZmZSb1JTQ1BlZWFmLzFGdE9YWEVNV3ZFWWhNYVEwdHg2K3VGd0NRUGVSeitVVnIvZHgyb2c1K2srRWJENStvbStCaW9JZCtjZHhYOW9jVU5MdmVuYitHYWdrZkZaMDQ0ekJQYkxjUDlHaWpJNlhvWS82OVErbC9IemlrNGRTTEpZVlBTK01xM0gzZi8wemE3RjlrU0pWc3MzN2JURXlWSFdMajllSmszcjBGOXg3eUFSVkZpNWpiVVpqcGkvSC83ZTd4VEtHdytJT1hWd0oyYkx6S2tzWTdUUTVER2lUWUduS01MRlRrdTU4d2Qrajk0aVlRdVpsRkhlMFk4Vy9qcFF5cGdFTUpJcXByMThkSVAzK1NSOW5MYlEzQVFjQXE4YzROMC9xeEg3NjUwVHcwdzgyUURueS8vYVNvakNISmNHbVEwSkt5YzhkbFlDWEZWVlBwdlR5Z21YUnRVZEZQUVVDQm1WZEF6akY4Ykw4ejRZRXUyNzBqRkRlRU42QVE4dmlTRmg4Y1pKVUFxRjNiNFc5Kzg0eDdUdlNzYjRPTmtqRElHRDdscll0aCsrMVd3UzhRbTllZ283U2RRSW08L2RzOlNpZ25hdHVyZVZhbHVlPgo8ZHM6S2V5SW5mbz48ZHM6WDUwOURhdGE+PGRzOlg1MDlDZXJ0aWZpY2F0ZT5NSUlFQ3pDQ0FuTUNGQjV5SDYyT01EczVMOHVub0pleUpPQkszclE5TUEwR0NTcUdTSWIzRFFFQkN3VUFNRUl4RnpBVkJnTlZCQU1NRG5OcGJYQnNaWE5oYld3Z1NXUlFNU2N3SlFZRFZRUUtEQjVFWlhabGJHOXdiV1Z1ZENCRWIyTnJaWElnWlc1MmFYSnZibTFsYm5Rd0hoY05Nak13TlRJMU1Ea3pNekUxV2hjTk1qZ3dOVEl6TURrek16RTFXakJDTVJjd0ZRWURWUVFEREE1emFXMXdiR1Z6WVcxc0lFbGtVREVuTUNVR0ExVUVDZ3dlUkdWMlpXeHZjRzFsYm5RZ1JHOWphMlZ5SUdWdWRtbHliMjV0Wlc1ME1JSUJvakFOQmdrcWhraUc5dzBCQVFFRkFBT0NBWThBTUlJQmlnS0NBWUVBd1Q5YndXZkVQQUhISEZtS21IM01CYklBZGcxMGdER3F5T0I1YlY3M0tZL1VaRzUzV3F4MzBSTnoydHV2UDJIR24xVlczaENLME5NMG5iWmJySVlBdXZ3ZlI3UVpIVzk4L0dyWU1ZdFpXQWZxYjJYeFdYL3VpclFqWFFOdHh0cDNnRU43dTIzcVpkc0tuNkpFMURtRlh4N2xmY0czNjcrSVhyWWRPNXlTSWVUWjdWWEFHZVhnZWF1YkZBUWpPTHFwb2xjSU0vRFR5RDRUZmozdldRYUhjeFZ2cnBNcy9NQ1pNT0dQaGF6Mjl0cnVPRUFmYlZhR3VidStkQkx0TWU3TUgvZW9nVW1lT1U1WHc2TUZ4bmtXQmVCeXprOGJPSjVibVJONlBlNFljSGpteWR5bE1IcW5OTGQ2STJpQngrR0Rud3dWM3NUOUU4WHpZVkh1QzI4OGNQSGdvU2lLdGJFY3VmbEJzOXpuMXNNTVhXN0NaVWxFV1lBeEJOL2ZHK1VER0JFeDBOVGpFb3dyWVBVZlA0QnpIUVNUa3YvVTJYRFRUQjJwbWMxNFZqMUptaDY4K2RZTHVGSitEYXlldDhnakM2WHdqZFBuUWROTjl3WGpQS3o5MzhxU3JrUHNhZjBHM3JOaFd1SG5rN25kYWhPTGlpYm5xc2tMejJGL1NYaFhBZ01CQUFFd0RRWUpLb1pJaHZjTkFRRUxCUUFEZ2dHQkFMRXZvOURLb29PVHVuMFRjU2pUNHppdW13dmtEZVI4eUlEdUhOcXNFNGJOSktwL294aVNEUkRReUExQVQ3cVBiVGFiY2dPdzB3eEhHQjFpRlkydXJtWEtjdTJyeWpzeG5ON00xM2ZVRTZuNloyWis5dTNadUhCNGhCQWIxV05kalN0ckdleHlSZVRrT2VGdFRCM1hqWlh4TWYvRnJRN2I4NFVQYi96VXl1eitraFNGVUFOMDNaZGY3UEViR2F2M1NGZHNhTFFvQ3REV3JKVGR1RzNKRG5vRzNYK2tXWThoSVl1UWhtenFZVjFMeEpEWDlTQlBZQzJiZ2lNOEFmbnpRVnhkVjlwUXFpdWFpTTlMLzl3UDFaT0JqdWREcmd1T1BCMm1vNC93cUVLZEpRc1Zuc1hwNUdKdlMyOGZJM0d5c3VhS1piY3F3d09iZHc4eHpRbmlsOHRVWmFseDJQVW5UaURQTnY3eW9BQ1JJYzRqTkZMaVUrZk5VTGFVNEtiVDNYamZtQXpKVWZ0SEcrTzYwNTIybHFWQzY3a0Erdk93SUpiei9TaWlrQm5rbVQ1a3I0ZGZ0Ymp0M1lUMjJrVm1hVjBCQndNZzBjdkpNSHF1bzhxamcxTHdhRG1HajdDQXFSK09wRlVHbkgxQ0ZjZFQwVjJzdlFYMlhLa0k1TzhPOWc9PTwvZHM6WDUwOUNlcnRpZmljYXRlPjwvZHM6WDUwOURhdGE+PC9kczpLZXlJbmZvPjwvZHM6U2lnbmF0dXJlPjxzYW1sOlN1YmplY3Q+PHNhbWw6TmFtZUlEIEZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6MS4xOm5hbWVpZC1mb3JtYXQ6dW5zcGVjaWZpZWQiPnVybjpjb2xsYWI6cGVyc29uOmluc3RpdHV0aW9uLWEuZXhhbXBsZS5jb206amFuZS1hMTwvc2FtbDpOYW1lSUQ+PHNhbWw6U3ViamVjdENvbmZpcm1hdGlvbiBNZXRob2Q9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDpjbTpiZWFyZXIiPjxzYW1sOlN1YmplY3RDb25maXJtYXRpb25EYXRhIE5vdE9uT3JBZnRlcj0iMjAyNi0xMC0yN1QxMDo0NTo1N1oiIFJlY2lwaWVudD0iaHR0cHM6Ly9nYXRld2F5LmRldi5vcGVuY29uZXh0LmxvY2FsL2F1dGhlbnRpY2F0aW9uL2NvbnN1bWUtYXNzZXJ0aW9uIiBJblJlc3BvbnNlVG89Il9kOGI0N2ZlNjc5YTJlMjBkYmUxNTU5MTAwZmUxM2U4NzgxMjA1YTk0YTlmNDc3ZTliMGZmNGU0ZWQ0YTMiLz48L3NhbWw6U3ViamVjdENvbmZpcm1hdGlvbj48L3NhbWw6U3ViamVjdD48c2FtbDpDb25kaXRpb25zIE5vdEJlZm9yZT0iMjAyNi0xMC0yN1QxMDo0MDoyN1oiIE5vdE9uT3JBZnRlcj0iMjAyNi0xMC0yN1QxMDo0NTo1N1oiPjxzYW1sOkF1ZGllbmNlUmVzdHJpY3Rpb24+PHNhbWw6QXVkaWVuY2U+aHR0cHM6Ly9nYXRld2F5LmRldi5vcGVuY29uZXh0LmxvY2FsL2F1dGhlbnRpY2F0aW9uL21ldGFkYXRhPC9zYW1sOkF1ZGllbmNlPjwvc2FtbDpBdWRpZW5jZVJlc3RyaWN0aW9uPjwvc2FtbDpDb25kaXRpb25zPjxzYW1sOkF1dGhuU3RhdGVtZW50IEF1dGhuSW5zdGFudD0iMjAyNi0xMC0yN1QxMDo0MDo1N1oiIFNlc3Npb25Ob3RPbk9yQWZ0ZXI9IjIwMjYtMTAtMjdUMTg6NDA6NTdaIiBTZXNzaW9uSW5kZXg9Il9iOTRlOWM1MjY3NDQyOWY5NDEwNzMxYjIyNjRjMGU2ODkxZWY0ZGRjZjEiPjxzYW1sOkF1dGhuQ29udGV4dD48c2FtbDpBdXRobkNvbnRleHRDbGFzc1JlZj51cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YWM6Y2xhc3NlczpQYXNzd29yZFByb3RlY3RlZFRyYW5zcG9ydDwvc2FtbDpBdXRobkNvbnRleHRDbGFzc1JlZj48L3NhbWw6QXV0aG5Db250ZXh0Pjwvc2FtbDpBdXRoblN0YXRlbWVudD48c2FtbDpBdHRyaWJ1dGVTdGF0ZW1lbnQ+PHNhbWw6QXR0cmlidXRlIE5hbWU9InVybjptYWNlOnN1cmYubmw6YXR0cmlidXRlLWRlZjppbnRlcm5hbC1jb2xsYWJQZXJzb25JZCIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPnVybjpjb2xsYWI6cGVyc29uOmluc3RpdHV0aW9uLWEuZXhhbXBsZS5jb206amFuZS1hMTwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTpkaXI6YXR0cmlidXRlLWRlZjp1aWQiIE5hbWVGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48c2FtbDpBdHRyaWJ1dGVWYWx1ZSB4c2k6dHlwZT0ieHM6c3RyaW5nIj5qYW5lLWExPC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PHNhbWw6QXR0cmlidXRlIE5hbWU9InVybjptYWNlOmRpcjphdHRyaWJ1dGUtZGVmOmVkdVBlcnNvblByaW5jaXBhbE5hbWUiIE5hbWVGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48c2FtbDpBdHRyaWJ1dGVWYWx1ZSB4c2k6dHlwZT0ieHM6c3RyaW5nIj5qYW5lLWExQGluc3RpdHV0aW9uLWEuZXhhbXBsZS5jb208L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgTmFtZT0idXJuOm1hY2U6ZGlyOmF0dHJpYnV0ZS1kZWY6Z2l2ZW5OYW1lIiBOYW1lRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YXR0cm5hbWUtZm9ybWF0OnVyaSI+PHNhbWw6QXR0cmlidXRlVmFsdWUgeHNpOnR5cGU9InhzOnN0cmluZyI+Z24tamFuZS1hMTwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTpkaXI6YXR0cmlidXRlLWRlZjpzbiIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPnNuLWluc3RpdHV0aW9uLWEuZXhhbXBsZS5jb208L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgTmFtZT0idXJuOm1hY2U6ZGlyOmF0dHJpYnV0ZS1kZWY6Y24iIE5hbWVGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48c2FtbDpBdHRyaWJ1dGVWYWx1ZSB4c2k6dHlwZT0ieHM6c3RyaW5nIj5qYW5lLWExIGluc3RpdHV0aW9uLWEuZXhhbXBsZS5jb208L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgTmFtZT0idXJuOm1hY2U6ZGlyOmF0dHJpYnV0ZS1kZWY6bWFpbCIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPmphbmUramFuZS1hMUBkZXYub3BlbmNvbmV4dC5sb2NhbDwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTpkaXI6YXR0cmlidXRlLWRlZjpkaXNwbGF5TmFtZSIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPmQtamFuZS1hMSBpbnN0aXR1dGlvbi1hLmV4YW1wbGUuY29tPC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PHNhbWw6QXR0cmlidXRlIE5hbWU9InVybjptYWNlOmRpcjphdHRyaWJ1dGUtZGVmOmVkdVBlcnNvbkFmZmlsaWF0aW9uIiBOYW1lRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YXR0cm5hbWUtZm9ybWF0OnVyaSI+PHNhbWw6QXR0cmlidXRlVmFsdWUgeHNpOnR5cGU9InhzOnN0cmluZyI+c3R1ZGVudDwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTp0ZXJlbmEub3JnOmF0dHJpYnV0ZS1kZWY6c2NoYWNIb21lT3JnYW5pemF0aW9uIiBOYW1lRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YXR0cm5hbWUtZm9ybWF0OnVyaSI+PHNhbWw6QXR0cmlidXRlVmFsdWUgeHNpOnR5cGU9InhzOnN0cmluZyI+aW5zdGl0dXRpb24tYS5leGFtcGxlLmNvbTwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTp0ZXJlbmEub3JnOmF0dHJpYnV0ZS1kZWY6c2NoYWNIb21lT3JnYW5pemF0aW9uVHlwZSIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPnVybjptYWNlOnRlcmVuYS5vcmc6c2NoYWM6aG9tZU9yZ2FuaXphdGlvblR5cGU6aW50OnVuaXZlcnNpdHk8L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48L3NhbWw6QXR0cmlidXRlU3RhdGVtZW50Pjwvc2FtbDpBc3NlcnRpb24+PC9zYW1scDpSZXNwb25zZT4=';
