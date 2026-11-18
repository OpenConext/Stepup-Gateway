<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: response-post
 * Binding: HTTP-POST
 * Fixture name: ssp-response-joe-a-yk
 * Generated: 2026-10-27T10:39:53+00:00
 *
 * This is an HTTP-POST Response (base64-encoded XML).
 * Captured from: SAML Tracer or browser DevTools form data
 * Format: Base64-encoded XML value of SAMLResponse parameter
 * Note: HTTP-Redirect Response does not exist - responses always use HTTP-POST
 *
 * Message Summary:
 *   - Binding: HTTP-POST (base64-encoded XML)
 *   - XML size: 7377 bytes
 *   - Signed: Yes (XML signature)
 *   - Signature Algorithm: http://www.w3.org/2001/04/xmldsig-more#rsa-sha256
 *   - Issuer: https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php
 *   - NameID: urn:collab:person:institution-a.example.com:joe-a-yk
 *   - AuthnContextClassRef: urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport
 *   - StatusCode: urn:oasis:names:tc:SAML:2.0:status:Success
 *   - Audience: https://gateway.dev.openconext.local/authentication/metadata
 */

/*
Decoded XML:

<?xml version="1.0"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_36410cd306ff5afe9d9a131d1d324dda5b4f09fc24" Version="2.0" IssueInstant="2026-10-27T10:34:56Z" Destination="https://gateway.dev.openconext.local/authentication/consume-assertion" InResponseTo="_095c1a4bdd6588d1b931e4cd3933b8d75dcc77a898e5fcd68233f07ad9c6">
  <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" ID="_0faac21aed052398de8e7604b1056fbb86144535b1" Version="2.0" IssueInstant="2026-10-27T10:34:56Z">
    <saml:Issuer>https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#_0faac21aed052398de8e7604b1056fbb86144535b1">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>zWj1yYvn6NFBH7/DwpKX8jm4AfWCvONhPWT8p+V4Rik=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>NNnI2UoGkUijt6/baTfs+mRMRErsQ3noZGQPw40udTgbHzIZIcgDPSs9Tciv4fGUY/zc210IhvdOXPKTHDSuZ6FYqeO4gMyWZNWs1F+VtEet49U+RrUWBW5jcazU3svqJbj7LRhsPbVCzGmjcVyzRw7MfoPBWCbfiK6ULTLAnTZaA6jWNqV7R6oQelb/kmjqXzqG+i+3H9QHlArf3tiJzc+MxoMuGlArJCMvqcE2Pz6qsFcjMRi2HbqYdd9vjIwRQoi1SjYty3Ioua6Vhkmz/ghJ1b/fWj45JyYpFI0ME2eI6wo3vdqvlpoXfA5tRTmzroUpAmahrYQMTBbOjl0BG65Cl8jdD/wkIUJF5R2/s7VnTVd2x5dwHcLf/kPVeDNHVcKY+T6gkJ1R80ok4gfRzPqCfScrOGykd/JlW1PNqhNzFG9fFLafe4yHZHuR8prqnWevlsnPObe6uIajcXErghIOb3AfmrQypk7bT7YudP0KY+ANNNj8KCiASyDdRSlw</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MIIECzCCAnMCFB5yH62OMDs5L8unoJeyJOBK3rQ9MA0GCSqGSIb3DQEBCwUAMEIxFzAVBgNVBAMMDnNpbXBsZXNhbWwgSWRQMScwJQYDVQQKDB5EZXZlbG9wbWVudCBEb2NrZXIgZW52aXJvbm1lbnQwHhcNMjMwNTI1MDkzMzE1WhcNMjgwNTIzMDkzMzE1WjBCMRcwFQYDVQQDDA5zaW1wbGVzYW1sIElkUDEnMCUGA1UECgweRGV2ZWxvcG1lbnQgRG9ja2VyIGVudmlyb25tZW50MIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAwT9bwWfEPAHHHFmKmH3MBbIAdg10gDGqyOB5bV73KY/UZG53Wqx30RNz2tuvP2HGn1VW3hCK0NM0nbZbrIYAuvwfR7QZHW98/GrYMYtZWAfqb2XxWX/uirQjXQNtxtp3gEN7u23qZdsKn6JE1DmFXx7lfcG367+IXrYdO5ySIeTZ7VXAGeXgeaubFAQjOLqpolcIM/DTyD4Tfj3vWQaHcxVvrpMs/MCZMOGPhaz29truOEAfbVaGubu+dBLtMe7MH/eogUmeOU5Xw6MFxnkWBeByzk8bOJ5bmRN6Pe4YcHjmydylMHqnNLd6I2iBx+GDnwwV3sT9E8XzYVHuC288cPHgoSiKtbEcuflBs9zn1sMMXW7CZUlEWYAxBN/fG+UDGBEx0NTjEowrYPUfP4BzHQSTkv/U2XDTTB2pmc14Vj1Jmh68+dYLuFJ+Dayet8gjC6XwjdPnQdNN9wXjPKz938qSrkPsaf0G3rNhWuHnk7ndahOLiibnqskLz2F/SXhXAgMBAAEwDQYJKoZIhvcNAQELBQADggGBALEvo9DKooOTun0TcSjT4ziumwvkDeR8yIDuHNqsE4bNJKp/oxiSDRDQyA1AT7qPbTabcgOw0wxHGB1iFY2urmXKcu2ryjsxnN7M13fUE6n6Z2Z+9u3ZuHB4hBAb1WNdjStrGexyReTkOeFtTB3XjZXxMf/FrQ7b84UPb/zUyuz+khSFUAN03Zdf7PEbGav3SFdsaLQoCtDWrJTduG3JDnoG3X+kWY8hIYuQhmzqYV1LxJDX9SBPYC2bgiM8AfnzQVxdV9pQqiuaiM9L/9wP1ZOBjudDrguOPB2mo4/wqEKdJQsVnsXp5GJvS28fI3GysuaKZbcqwwObdw8xzQnil8tUZalx2PUnTiDPNv7yoACRIc4jNFLiU+fNULaU4KbT3XjfmAzJUftHG+O60522lqVC67kA+vOwIJbz/SiikBnkmT5kr4dftbjt3YT22kVmaV0BBwMg0cvJMHquo8qjg1LwaDmGj7CAqR+OpFUGnH1CFcdT0V2svQX2XKkI5O8O9g==</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:institution-a.example.com:joe-a-yk</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData NotOnOrAfter="2026-10-27T10:39:56Z" Recipient="https://gateway.dev.openconext.local/authentication/consume-assertion" InResponseTo="_095c1a4bdd6588d1b931e4cd3933b8d75dcc77a898e5fcd68233f07ad9c6"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2026-10-27T10:34:26Z" NotOnOrAfter="2026-10-27T10:39:56Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://gateway.dev.openconext.local/authentication/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement AuthnInstant="2026-10-27T10:34:56Z" SessionNotOnOrAfter="2026-10-27T18:34:56Z" SessionIndex="_2759fcf6181a142f1764082aa214575205851e1280">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef>
      </saml:AuthnContext>
    </saml:AuthnStatement>
    <saml:AttributeStatement>
      <saml:Attribute Name="urn:mace:surf.nl:attribute-def:internal-collabPersonId" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">urn:collab:person:institution-a.example.com:joe-a-yk</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:uid" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">joe-a-yk</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:eduPersonPrincipalName" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">joe-a-yk@institution-a.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:givenName" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">gn-joe-a-yk</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:sn" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">sn-institution-a.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:cn" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">joe-a-yk institution-a.example.com</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:mail" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">joe+joe-a-yk@dev.openconext.local</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:mace:dir:attribute-def:displayName" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">d-joe-a-yk institution-a.example.com</saml:AttributeValue>
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

return 'PHNhbWxwOlJlc3BvbnNlIHhtbG5zOnNhbWxwPSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6cHJvdG9jb2wiIHhtbG5zOnNhbWw9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphc3NlcnRpb24iIElEPSJfMzY0MTBjZDMwNmZmNWFmZTlkOWExMzFkMWQzMjRkZGE1YjRmMDlmYzI0IiBWZXJzaW9uPSIyLjAiIElzc3VlSW5zdGFudD0iMjAyNi0xMC0yN1QxMDozNDo1NloiIERlc3RpbmF0aW9uPSJodHRwczovL2dhdGV3YXkuZGV2Lm9wZW5jb25leHQubG9jYWwvYXV0aGVudGljYXRpb24vY29uc3VtZS1hc3NlcnRpb24iIEluUmVzcG9uc2VUbz0iXzA5NWMxYTRiZGQ2NTg4ZDFiOTMxZTRjZDM5MzNiOGQ3NWRjYzc3YTg5OGU1ZmNkNjgyMzNmMDdhZDljNiI+PHNhbWw6SXNzdWVyPmh0dHBzOi8vc3NwLmRldi5vcGVuY29uZXh0LmxvY2FsL3NpbXBsZXNhbWwvc2FtbDIvaWRwL21ldGFkYXRhLnBocDwvc2FtbDpJc3N1ZXI+PHNhbWxwOlN0YXR1cz48c2FtbHA6U3RhdHVzQ29kZSBWYWx1ZT0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOnN0YXR1czpTdWNjZXNzIi8+PC9zYW1scDpTdGF0dXM+PHNhbWw6QXNzZXJ0aW9uIHhtbG5zOnhzaT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS9YTUxTY2hlbWEtaW5zdGFuY2UiIHhtbG5zOnhzPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxL1hNTFNjaGVtYSIgSUQ9Il8wZmFhYzIxYWVkMDUyMzk4ZGU4ZTc2MDRiMTA1NmZiYjg2MTQ0NTM1YjEiIFZlcnNpb249IjIuMCIgSXNzdWVJbnN0YW50PSIyMDI2LTEwLTI3VDEwOjM0OjU2WiI+PHNhbWw6SXNzdWVyPmh0dHBzOi8vc3NwLmRldi5vcGVuY29uZXh0LmxvY2FsL3NpbXBsZXNhbWwvc2FtbDIvaWRwL21ldGFkYXRhLnBocDwvc2FtbDpJc3N1ZXI+PGRzOlNpZ25hdHVyZSB4bWxuczpkcz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnIyI+CiAgPGRzOlNpZ25lZEluZm8+PGRzOkNhbm9uaWNhbGl6YXRpb25NZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzEwL3htbC1leGMtYzE0biMiLz4KICAgIDxkczpTaWduYXR1cmVNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGRzaWctbW9yZSNyc2Etc2hhMjU2Ii8+CiAgPGRzOlJlZmVyZW5jZSBVUkk9IiNfMGZhYWMyMWFlZDA1MjM5OGRlOGU3NjA0YjEwNTZmYmI4NjE0NDUzNWIxIj48ZHM6VHJhbnNmb3Jtcz48ZHM6VHJhbnNmb3JtIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnI2VudmVsb3BlZC1zaWduYXR1cmUiLz48ZHM6VHJhbnNmb3JtIEFsZ29yaXRobT0iaHR0cDovL3d3dy53My5vcmcvMjAwMS8xMC94bWwtZXhjLWMxNG4jIi8+PC9kczpUcmFuc2Zvcm1zPjxkczpEaWdlc3RNZXRob2QgQWxnb3JpdGhtPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxLzA0L3htbGVuYyNzaGEyNTYiLz48ZHM6RGlnZXN0VmFsdWU+eldqMXlZdm42TkZCSDcvRHdwS1g4am00QWZXQ3ZPTmhQV1Q4cCtWNFJpaz08L2RzOkRpZ2VzdFZhbHVlPjwvZHM6UmVmZXJlbmNlPjwvZHM6U2lnbmVkSW5mbz48ZHM6U2lnbmF0dXJlVmFsdWU+Tk5uSTJVb0drVWlqdDYvYmFUZnMrbVJNUkVyc1Ezbm9aR1FQdzQwdWRUZ2JIeklaSWNnRFBTczlUY2l2NGZHVVkvemMyMTBJaHZkT1hQS1RIRFN1WjZGWXFlTzRnTXlXWk5XczFGK1Z0RWV0NDlVK1JyVVdCVzVqY2F6VTNzdnFKYmo3TFJoc1BiVkN6R21qY1Z5elJ3N01mb1BCV0NiZmlLNlVMVExBblRaYUE2aldOcVY3UjZvUWVsYi9rbWpxWHpxRytpKzNIOVFIbEFyZjN0aUp6YytNeG9NdUdsQXJKQ012cWNFMlB6NnFzRmNqTVJpMkhicVlkZDl2akl3UlFvaTFTall0eTNJb3VhNlZoa216L2doSjFiL2ZXajQ1SnlZcEZJME1FMmVJNndvM3ZkcXZscG9YZkE1dFJUbXpyb1VwQW1haHJZUU1UQmJPamwwQkc2NUNsOGpkRC93a0lVSkY1UjIvczdWblRWZDJ4NWR3SGNMZi9rUFZlRE5IVmNLWStUNmdrSjFSODBvazRnZlJ6UHFDZlNjck9HeWtkL0psVzFQTnFoTnpGRzlmRkxhZmU0eUhaSHVSOHBycW5XZXZsc25QT2JlNnVJYWpjWEVyZ2hJT2IzQWZtclF5cGs3YlQ3WXVkUDBLWStBTk5OajhLQ2lBU3lEZFJTbHc8L2RzOlNpZ25hdHVyZVZhbHVlPgo8ZHM6S2V5SW5mbz48ZHM6WDUwOURhdGE+PGRzOlg1MDlDZXJ0aWZpY2F0ZT5NSUlFQ3pDQ0FuTUNGQjV5SDYyT01EczVMOHVub0pleUpPQkszclE5TUEwR0NTcUdTSWIzRFFFQkN3VUFNRUl4RnpBVkJnTlZCQU1NRG5OcGJYQnNaWE5oYld3Z1NXUlFNU2N3SlFZRFZRUUtEQjVFWlhabGJHOXdiV1Z1ZENCRWIyTnJaWElnWlc1MmFYSnZibTFsYm5Rd0hoY05Nak13TlRJMU1Ea3pNekUxV2hjTk1qZ3dOVEl6TURrek16RTFXakJDTVJjd0ZRWURWUVFEREE1emFXMXdiR1Z6WVcxc0lFbGtVREVuTUNVR0ExVUVDZ3dlUkdWMlpXeHZjRzFsYm5RZ1JHOWphMlZ5SUdWdWRtbHliMjV0Wlc1ME1JSUJvakFOQmdrcWhraUc5dzBCQVFFRkFBT0NBWThBTUlJQmlnS0NBWUVBd1Q5YndXZkVQQUhISEZtS21IM01CYklBZGcxMGdER3F5T0I1YlY3M0tZL1VaRzUzV3F4MzBSTnoydHV2UDJIR24xVlczaENLME5NMG5iWmJySVlBdXZ3ZlI3UVpIVzk4L0dyWU1ZdFpXQWZxYjJYeFdYL3VpclFqWFFOdHh0cDNnRU43dTIzcVpkc0tuNkpFMURtRlh4N2xmY0czNjcrSVhyWWRPNXlTSWVUWjdWWEFHZVhnZWF1YkZBUWpPTHFwb2xjSU0vRFR5RDRUZmozdldRYUhjeFZ2cnBNcy9NQ1pNT0dQaGF6Mjl0cnVPRUFmYlZhR3VidStkQkx0TWU3TUgvZW9nVW1lT1U1WHc2TUZ4bmtXQmVCeXprOGJPSjVibVJONlBlNFljSGpteWR5bE1IcW5OTGQ2STJpQngrR0Rud3dWM3NUOUU4WHpZVkh1QzI4OGNQSGdvU2lLdGJFY3VmbEJzOXpuMXNNTVhXN0NaVWxFV1lBeEJOL2ZHK1VER0JFeDBOVGpFb3dyWVBVZlA0QnpIUVNUa3YvVTJYRFRUQjJwbWMxNFZqMUptaDY4K2RZTHVGSitEYXlldDhnakM2WHdqZFBuUWROTjl3WGpQS3o5MzhxU3JrUHNhZjBHM3JOaFd1SG5rN25kYWhPTGlpYm5xc2tMejJGL1NYaFhBZ01CQUFFd0RRWUpLb1pJaHZjTkFRRUxCUUFEZ2dHQkFMRXZvOURLb29PVHVuMFRjU2pUNHppdW13dmtEZVI4eUlEdUhOcXNFNGJOSktwL294aVNEUkRReUExQVQ3cVBiVGFiY2dPdzB3eEhHQjFpRlkydXJtWEtjdTJyeWpzeG5ON00xM2ZVRTZuNloyWis5dTNadUhCNGhCQWIxV05kalN0ckdleHlSZVRrT2VGdFRCM1hqWlh4TWYvRnJRN2I4NFVQYi96VXl1eitraFNGVUFOMDNaZGY3UEViR2F2M1NGZHNhTFFvQ3REV3JKVGR1RzNKRG5vRzNYK2tXWThoSVl1UWhtenFZVjFMeEpEWDlTQlBZQzJiZ2lNOEFmbnpRVnhkVjlwUXFpdWFpTTlMLzl3UDFaT0JqdWREcmd1T1BCMm1vNC93cUVLZEpRc1Zuc1hwNUdKdlMyOGZJM0d5c3VhS1piY3F3d09iZHc4eHpRbmlsOHRVWmFseDJQVW5UaURQTnY3eW9BQ1JJYzRqTkZMaVUrZk5VTGFVNEtiVDNYamZtQXpKVWZ0SEcrTzYwNTIybHFWQzY3a0Erdk93SUpiei9TaWlrQm5rbVQ1a3I0ZGZ0Ymp0M1lUMjJrVm1hVjBCQndNZzBjdkpNSHF1bzhxamcxTHdhRG1HajdDQXFSK09wRlVHbkgxQ0ZjZFQwVjJzdlFYMlhLa0k1TzhPOWc9PTwvZHM6WDUwOUNlcnRpZmljYXRlPjwvZHM6WDUwOURhdGE+PC9kczpLZXlJbmZvPjwvZHM6U2lnbmF0dXJlPjxzYW1sOlN1YmplY3Q+PHNhbWw6TmFtZUlEIEZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6MS4xOm5hbWVpZC1mb3JtYXQ6dW5zcGVjaWZpZWQiPnVybjpjb2xsYWI6cGVyc29uOmluc3RpdHV0aW9uLWEuZXhhbXBsZS5jb206am9lLWEteWs8L3NhbWw6TmFtZUlEPjxzYW1sOlN1YmplY3RDb25maXJtYXRpb24gTWV0aG9kPSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6Y206YmVhcmVyIj48c2FtbDpTdWJqZWN0Q29uZmlybWF0aW9uRGF0YSBOb3RPbk9yQWZ0ZXI9IjIwMjYtMTAtMjdUMTA6Mzk6NTZaIiBSZWNpcGllbnQ9Imh0dHBzOi8vZ2F0ZXdheS5kZXYub3BlbmNvbmV4dC5sb2NhbC9hdXRoZW50aWNhdGlvbi9jb25zdW1lLWFzc2VydGlvbiIgSW5SZXNwb25zZVRvPSJfMDk1YzFhNGJkZDY1ODhkMWI5MzFlNGNkMzkzM2I4ZDc1ZGNjNzdhODk4ZTVmY2Q2ODIzM2YwN2FkOWM2Ii8+PC9zYW1sOlN1YmplY3RDb25maXJtYXRpb24+PC9zYW1sOlN1YmplY3Q+PHNhbWw6Q29uZGl0aW9ucyBOb3RCZWZvcmU9IjIwMjYtMTAtMjdUMTA6MzQ6MjZaIiBOb3RPbk9yQWZ0ZXI9IjIwMjYtMTAtMjdUMTA6Mzk6NTZaIj48c2FtbDpBdWRpZW5jZVJlc3RyaWN0aW9uPjxzYW1sOkF1ZGllbmNlPmh0dHBzOi8vZ2F0ZXdheS5kZXYub3BlbmNvbmV4dC5sb2NhbC9hdXRoZW50aWNhdGlvbi9tZXRhZGF0YTwvc2FtbDpBdWRpZW5jZT48L3NhbWw6QXVkaWVuY2VSZXN0cmljdGlvbj48L3NhbWw6Q29uZGl0aW9ucz48c2FtbDpBdXRoblN0YXRlbWVudCBBdXRobkluc3RhbnQ9IjIwMjYtMTAtMjdUMTA6MzQ6NTZaIiBTZXNzaW9uTm90T25PckFmdGVyPSIyMDI2LTEwLTI3VDE4OjM0OjU2WiIgU2Vzc2lvbkluZGV4PSJfMjc1OWZjZjYxODFhMTQyZjE3NjQwODJhYTIxNDU3NTIwNTg1MWUxMjgwIj48c2FtbDpBdXRobkNvbnRleHQ+PHNhbWw6QXV0aG5Db250ZXh0Q2xhc3NSZWY+dXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmFjOmNsYXNzZXM6UGFzc3dvcmRQcm90ZWN0ZWRUcmFuc3BvcnQ8L3NhbWw6QXV0aG5Db250ZXh0Q2xhc3NSZWY+PC9zYW1sOkF1dGhuQ29udGV4dD48L3NhbWw6QXV0aG5TdGF0ZW1lbnQ+PHNhbWw6QXR0cmlidXRlU3RhdGVtZW50PjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTpzdXJmLm5sOmF0dHJpYnV0ZS1kZWY6aW50ZXJuYWwtY29sbGFiUGVyc29uSWQiIE5hbWVGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48c2FtbDpBdHRyaWJ1dGVWYWx1ZSB4c2k6dHlwZT0ieHM6c3RyaW5nIj51cm46Y29sbGFiOnBlcnNvbjppbnN0aXR1dGlvbi1hLmV4YW1wbGUuY29tOmpvZS1hLXlrPC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PHNhbWw6QXR0cmlidXRlIE5hbWU9InVybjptYWNlOmRpcjphdHRyaWJ1dGUtZGVmOnVpZCIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPmpvZS1hLXlrPC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PHNhbWw6QXR0cmlidXRlIE5hbWU9InVybjptYWNlOmRpcjphdHRyaWJ1dGUtZGVmOmVkdVBlcnNvblByaW5jaXBhbE5hbWUiIE5hbWVGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48c2FtbDpBdHRyaWJ1dGVWYWx1ZSB4c2k6dHlwZT0ieHM6c3RyaW5nIj5qb2UtYS15a0BpbnN0aXR1dGlvbi1hLmV4YW1wbGUuY29tPC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PHNhbWw6QXR0cmlidXRlIE5hbWU9InVybjptYWNlOmRpcjphdHRyaWJ1dGUtZGVmOmdpdmVuTmFtZSIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPmduLWpvZS1hLXlrPC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PHNhbWw6QXR0cmlidXRlIE5hbWU9InVybjptYWNlOmRpcjphdHRyaWJ1dGUtZGVmOnNuIiBOYW1lRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YXR0cm5hbWUtZm9ybWF0OnVyaSI+PHNhbWw6QXR0cmlidXRlVmFsdWUgeHNpOnR5cGU9InhzOnN0cmluZyI+c24taW5zdGl0dXRpb24tYS5leGFtcGxlLmNvbTwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTpkaXI6YXR0cmlidXRlLWRlZjpjbiIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPmpvZS1hLXlrIGluc3RpdHV0aW9uLWEuZXhhbXBsZS5jb208L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgTmFtZT0idXJuOm1hY2U6ZGlyOmF0dHJpYnV0ZS1kZWY6bWFpbCIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPmpvZStqb2UtYS15a0BkZXYub3BlbmNvbmV4dC5sb2NhbDwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTpkaXI6YXR0cmlidXRlLWRlZjpkaXNwbGF5TmFtZSIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPmQtam9lLWEteWsgaW5zdGl0dXRpb24tYS5leGFtcGxlLmNvbTwvc2FtbDpBdHRyaWJ1dGVWYWx1ZT48L3NhbWw6QXR0cmlidXRlPjxzYW1sOkF0dHJpYnV0ZSBOYW1lPSJ1cm46bWFjZTpkaXI6YXR0cmlidXRlLWRlZjplZHVQZXJzb25BZmZpbGlhdGlvbiIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPnN0dWRlbnQ8L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgTmFtZT0idXJuOm1hY2U6dGVyZW5hLm9yZzphdHRyaWJ1dGUtZGVmOnNjaGFjSG9tZU9yZ2FuaXphdGlvbiIgTmFtZUZvcm1hdD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmF0dHJuYW1lLWZvcm1hdDp1cmkiPjxzYW1sOkF0dHJpYnV0ZVZhbHVlIHhzaTp0eXBlPSJ4czpzdHJpbmciPmluc3RpdHV0aW9uLWEuZXhhbXBsZS5jb208L3NhbWw6QXR0cmlidXRlVmFsdWU+PC9zYW1sOkF0dHJpYnV0ZT48c2FtbDpBdHRyaWJ1dGUgTmFtZT0idXJuOm1hY2U6dGVyZW5hLm9yZzphdHRyaWJ1dGUtZGVmOnNjaGFjSG9tZU9yZ2FuaXphdGlvblR5cGUiIE5hbWVGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48c2FtbDpBdHRyaWJ1dGVWYWx1ZSB4c2k6dHlwZT0ieHM6c3RyaW5nIj51cm46bWFjZTp0ZXJlbmEub3JnOnNjaGFjOmhvbWVPcmdhbml6YXRpb25UeXBlOmludDp1bml2ZXJzaXR5PC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PC9zYW1sOkF0dHJpYnV0ZVN0YXRlbWVudD48L3NhbWw6QXNzZXJ0aW9uPjwvc2FtbHA6UmVzcG9uc2U+';
