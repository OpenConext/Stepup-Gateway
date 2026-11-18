<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: gssp-redirect-ra-jane-b1
 * Generated: 2026-11-04T19:12:13+00:00
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
 *   - SAMLRequest: Decoded (802 bytes XML)
 *   - Issuer: https://ra.dev.openconext.local/vetting-procedure/gssf/demo_gssp/metadata
 *   - Destination: https://gateway.dev.openconext.local/gssp/demo_gssp/single-sign-on
 *   - ID: _192843e1d259060866e04a06a3a5e6b05f5c06dcc5431254e733d0ebed3a
 *   - IssueInstant: 2026-11-04T18:48:58Z
 *   - NameID: test-prefix-6aeb7de7519694.37754665
 *   - AssertionConsumerServiceURL: https://ra.dev.openconext.local/vetting-procedure/gssf/demo_gssp/verify
 */

/*
Decoded XML:

<?xml version="1.0" encoding="UTF-8"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_192843e1d259060866e04a06a3a5e6b05f5c06dcc5431254e733d0ebed3a" Version="2.0" IssueInstant="2026-11-04T18:48:58Z" Destination="https://gateway.dev.openconext.local/gssp/demo_gssp/single-sign-on" AssertionConsumerServiceURL="https://ra.dev.openconext.local/vetting-procedure/gssf/demo_gssp/verify" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://ra.dev.openconext.local/vetting-procedure/gssf/demo_gssp/metadata</saml:Issuer>
  <saml:Subject>
    <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">test-prefix-6aeb7de7519694.37754665</saml:NameID>
  </saml:Subject>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=pVLNb5swFL%2F3r0C%2BA%2BbLECuhyhZVi9RtUUN32KUy9oN6Apthk6X%2F%2FRySbpG29bKb3%2FN7%2Fn15eXvsO%2B8Ao5FarVAUYOSB4lpI1a7QY3XnF%2Bi2vFka1ncDXU%2F2WT3A9wmM9dyiMnS%2BWKFpVFQzIw1VrAdDLaf79cd7GgeYDqO2musOXa28vcGMgdE6RsjbblboKVrERZpAJOJsgQkuCAGcMkxYwjIgNc6ajGMiOM%2FSJIqzFPIkERhqEAlD3pdXdfFJ3daYCbbKWKasa%2BGY%2BFHk47SKCpoWNCu%2BIm%2Fj9EnF7Lz1bO1gaBi2zMIP9hIIOAR6OJmk4GiDTnPWha0xQyig10%2FzyTj7OvCNbJV%2FUrF%2BFfReKzP1MO5hPEgOjw%2F3vwFG9ve3D2Adm9Z3PnIQ0wgntOYKzcUnmxfk7S5Gv5PqnN9bHtfnIUM%2FVNXO333eV6icY6azQ2P537R6sEwwy5bh9bNnjP1UfwNuL9Unx2678e702DP7b9pREM0dKfxmHqWTMgNw2UgQqLQuNccGGnn0CYM6F5Bn0YIs0iDJ8ywlJLtQOeOVl%2BoXl%2FDPX17e%2FAQ%3D&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=meZ3o5YPXa7DwzvjzF5bcBJUuEN4BJdj9BCvTA%2B2dlBHsyDX0H1kam2ceIoTUuxyb2j4%2BC6Sm%2BIx%2BWcmjQU7WVK0A4KPtO5GC%2FNpfo%2FA2wSh3WGemzxUu7%2BkYylmihinQv3%2FkuPxOv%2FopgxFFBsgyK%2B%2BRnbXiiMIW0H9RDqfjxhiduFc9FFHXZuyq9kulfnKKMjPFbxmYtrC0DpAwLedFJOaYkDtKEoDBsGk87StN48P%2F4suMvgET7UYGOhrgVPOYYnaOhxSWa9Gz%2FpudbNJk5aIqV6I8Aqt%2F3rUy7zu3tnMzfZmKgVxbt49pSfuKFBpd0N2DYCAOuaE6dfKoPw0gyxO2Nv2JKRNTYw%2Fan26%2BJVdfA%2B3xEFVTahRChZ1YtAxN88eTeVtVaey1x2pDihleZo56mVPwsp75wJ1fxSIiC6Z2Ljd%2FFcmuAjoFPXrbyq2tpAH6pheiuARokuKNeUZvkJ%2FttwcwiD9jPK9TYafDB3V%2BNwMUFe5HEqcUUTlHEH4';
