<?php
/**
 * WARNING: Generated using `tests/Functional/bin/convert-saml-to-fixture` do not edit manually
 *
 * Captured SAML message from devconf environment.
 *
 * Type: authn-request-redirect
 * Binding: HTTP-Redirect
 * Fixture name: selfservice-register-demo-gssp-2
 * Generated: 2026-11-04T19:05:05+00:00
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
 *   - ID: _050df1d29fbe52b15783a5e5e7c7a476a99dc88fc265853d842dca60eb9a
 *   - IssueInstant: 2026-11-04T18:48:07Z
 *   - AssertionConsumerServiceURL: https://selfservice.dev.openconext.local/registration/gssf/demo_gssp/consume-assertion
 *   - RelayState: isGssfRequest
 */

/*
Decoded XML:

<?xml version="1.0" encoding="UTF-8"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_050df1d29fbe52b15783a5e5e7c7a476a99dc88fc265853d842dca60eb9a" Version="2.0" IssueInstant="2026-11-04T18:48:07Z" Destination="https://gateway.dev.openconext.local/gssp/demo_gssp/single-sign-on" AssertionConsumerServiceURL="https://selfservice.dev.openconext.local/registration/gssf/demo_gssp/consume-assertion" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://selfservice.dev.openconext.local/registration/gssf/demo_gssp/metadata</saml:Issuer>
  <samlp:Extensions>
    <gssp:UserAttributes xmlns:gssp="urn:mace:surf.nl:stepup:gssp-extensions" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema"/>
  </samlp:Extensions>
</samlp:AuthnRequest>
*/

return 'SAMLRequest=rVNNb9swDL33Vxi6%2BzNx4giJi2zdsAApFjTOMOxSyDLtCLAlT5Tj7N%2FPH8maokN32U0g%2Bfj4Hqnl%2FbkqrRNoFEquiO94xALJVSZksSKH5LMdkfv4bomsKmu6bsxRPsHPBtBYHVAiHRIr0mhJFUOBVLIKkBpO9%2BvHLQ0cj9ZaGcVVSW4g7yMYImjTTUSszcOKPHuhl%2BV%2BFizyFMIg9cN5NGEhhDDnczadz9hikfEoynkwC6NwkkXTIONs5kG6YMT6dlUX9Oo2iA1sJBomTRfygpnt%2B7Y3TfyITiPqzX8Q66HTJyQzA%2BpoTI3UdQtmoGW%2FnAxOjqp7kyScjVMqzkq3QKzdDCr1PLyws68EG0Uh7V7F%2Biroo5LYVKD3oE%2BCw%2BFp%2B0KAUOY4xv9OoqEQaPQwV8%2BY3zDysbF9Y93u4vsHIcd1vmd5OhYh%2FZIkO3v3dZ%2BQeNg6HQzT8X%2BZsgLDMmbY0r1tfbmuT2cDst8Uxsu%2Bmh46nrUxWqSNAbxcT58ZpVSMA8VG544sKRqom3rI2vCn0fXkzihGnzsBbds67cRRunADz%2FPd74%2FbPT9CxWwxXAWHF9S%2FQcSNRzGv53fffpj47jc%3D&RelayState=isGssfRequest&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256&Signature=fpQamRu33feOhbnc3IveIRFU6VSmMJ4tSEjC9L7qNqrOl2yV%2F6h6ad55wWUBdb%2FxcYqHy5UcESYytAHEmxUnkQ4u5ni5MCXEoBWm0LK%2FUHG%2FKhAAGb7zFWdT%2BTqff8lC%2BE0nnO6XjDEhnTAwwfmfXHauHN94QTx%2F9Z9Ck0yQvQKYWlbq1092%2BitkoSf4gV96Oky7OKt2QBENjpfXdtxg8yK8WrjIxFaLQIH0gzLMdIbVh4P8KLsl6cIaiL8U2dxMXrnGu6aN7PcUUb8c6jpj1RJhocHV88JyA537jE25ahP%2FnlXJvz013DhqCmo%2B%2FAIMPXtr91h%2Bf9bvmi%2BxVLwTrYu6B1szeatmbC9ZcGy0mFy7THtJG2X0a7ai3Mh0kB9mXVhbry9ZmZFRinP4Yu91UCYdJzdSgpgO6CgeyhDAiWghJny4MNcUmoFPaYSXtFPokKy8fkdrJdBSEWO5uW%2BpVmEwi1I3m2ViHZI4ut4YfKqMNV4JX%2BwxRJZFYS4vhbaz';
