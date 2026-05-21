# GSSP Fallback

## Overview

The GSSP Fallback feature allows users to authenticate using a Generic SAML Second-factor Provider (GSSP) without having previously registered a token in Stepup. When a user has no **active** token in Stepup, the Gateway can transparently redirect the user to a configured fallback GSSP for authentication.

The primary use case is for institutions where users already have Microsoft Azure MFA enrolled through their institution's Microsoft tenant (e.g. for Office 365). These users can then use their existing Azure MFA credential for Stepup authentication without going through the Stepup self-service registration process.

Authentication via the GSSP Fallback is valued at **LoA 1.5** (the same as self-asserted tokens in Stepup), which is appropriate for use cases where additional identity validation is not required.

Because GSSP Fallback is triggered by the absence of an active token in Stepup, it allows users to switch to the GSSP Fallback authentication by simply removing their existing token(s) from Stepup using Stepup-SelfService, or by the RA using Stepup-RA. Conversely, a user can start using the tokens in Stepup by registering a token in Stepup-SelfSerivce. Note that once a user has an active token in Stepup, the GSSP Fallback is no longer available.

The GSSP Fallback is only active in the **Second Factor Only (SFO)** authentication flow. It is not available for normal authentications though the Stepup-Gateway.

For background information see [RFC-using-AzureMFA-without-registration.md](../rfcs/RFC-using-AzureMFA-without-registration.md).

## How it works

The SFO SP (e.g. OpenConext-engine) requesting authentication sends two extra parameters in the AuthnRequest using a SAML extension: the subject identifier to use for authentication the fallback GSSP (e.g. the user's email address) and the institution identifier. This is the schacHomeOrganization (SHO) as used in other places in the Stepup ecosystem, e.g. in the institution configuration and on the whitelist.

When all conditions for fallback are met, the Gateway sends a SAML AuthnRequest to the fallback GSSP, using `fallback_gssp_subject_attribute` as the Subject NameID. Note that the GSSP must support this because this is different from the normal GSSP behavior where the GSSP decides the Subject during registration. 

### Conditions for fallback activation

The fallback authenticaton to the fallback GSSP is triggered when **all the following** are true:

1. A fallback GSSP is configured in the Stepup-Gateway (`fallback_gssp` in parameters.yaml).
2. The authentication request is for LoA 1.5.
3. The AuthnRequest is sent to the Stepup-Gateway's SFO endpoint.
4. The AuthnRequest contains a [`UserAttributes` extension](UserAttributes.md) with `fallback_gssp_subject_attribute` and the `fallback_gssp_institution_attribute`.
5. The user's institution as provided in the `fallback_gssp_institution_attribute` is whitelisted in the Stepup-Gateway (via Stepup-Middleware).
6. The GSSP Fallback for this institution is enabled in the institution configuration (via Stepup-Middleware).
7. The user has no active token(s) in Stepup.

If any condition is not met, the Gateway continues with the normal authentication flow (which will result in the user being prompted to authenticate or in an error if no token is available).

### Authentication flow

```
SFO SP (e.g. OpenConext-engine) → Stepup-Gateway → Fallback GSSP (e.g. Stepup-AzureMFA)
    ↑                                                  |
    └──────────────────────────────────────────────────┘
```

1. **The SFO SP** sends a SAML AuthnRequest to the Stepup-Gateway SFO endpoint. This request includes a `UserAttributes` extension containing the two attributes configured in `fallback_gssp_subject_attribute` and `fallback_gssp_institution_attribute` in the gateway's parameters.yml.
2. **Stepup-Gateway** checks all fallback conditions. When all conditions are met, the Gateway sends a SAML AuthnRequest to the fallback GSSP, using `fallback_gssp_subject_attribute` as the Subject NameID.
3. **The fallback GSSP** (e.g. Stepup-AzureMFA) authenticates the user using Subject NameID in the AuthnRequest as the user ID.
4. **Stepup-Gateway** receives the SAML response from the GSSP and verifies that the subject in the response matches the subject that it sent. If it does not match, authentication fails.
5. **Stepup-Gateway** returns a successful authentication response to OpenConext-engine with LoA 1.5.

The user is **not** provisioned or registered in Stepup as a result of this flow. No token is created.

## Configuration

### Gateway configuration

The fallback GSSP is configured in the Gateway's `parameters.yaml`:

```yaml
# The ID of the GSSP to use as the fallback (e.g. "azuremfa"). Set to false to disable.
fallback_gssp: 'azuremfa'

# The attribute in the UserAttributes extension that contains the user's ID for the fallback GSSP
fallback_gssp_subject_attribute: 'urn:mace:dir:attribute-def:mail'

# The attribute used to determine the user's institution
fallback_gssp_institution_attribute: 'urn:mace:terena.org:attribute-def:schacHomeOrganization'
```

Set `fallback_gssp: false` to disable the feature globally (default). Set `fallback_gssp` to the ID of a `surfnet_stepup_gateway_saml_stepup_provider` in `samlstepupproviders.yaml` to enable the feature.

### Institution configuration

The fallback is enabled/disabled in the institution configuration via Stepup-Middleware. The configuration flag is `ssoRegistrationBypass`. This can be managed through the Stepup-Middleware API and is visible to RAAs in Stepup-RA.

For GSSP to be enabled for an institution, the configuration flag `ssoRegistrationBypass` must be set to `true` **and** the `fallback_gssp` parameter must be set to a GSSP. 

### Fallback GSSP requirements

The GSSP used as the fallback must accept the subject identifier provided by the SFO SP. (In the case of OpenConext-engine this will come from the Institution IdP). This means that coordination is required between the GSSP and the SFO SP. This is different from the normal GSSP flow, where the GSSP chooses the Subject during registration and will get that same Subject in the AuthnRequest for authentications.

For Stepup-AzureMFA, for example, this means the GSSP had to be changed to accept plain email addresses (UPNs) in addition to the `tokenId|` prefix that used for tokens that were registered using GSSP.

## Sending user attributes from OpenConext-engine

SFO SP is responsible for including the `UserAttributes` extension in the SFO AuthnRequest. The extension must contain the two attributes that are set in `fallback_gssp_subject_attribute` and `fallback_gssp_institution_attribute`. See [UserAttributes.md](UserAttributes.md) for the full specification.

Example SFO AuthnRequest with `UserAttributes` extension:

```xml
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                    ID="_09010524d6c616787a5b8364aa20a3bfcfa4a38062ee88c8893720717e83"
                    Version="2.0"
                    IssueInstant="2025-04-28T08:56:10Z"
                    Destination="https://gateway.stepup.example.org/second-factor-only/single-sign-on"
                    AssertionConsumerServiceURL="https://engine.openconext.example.org/authentication/stepup/consume-assertion"
                    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
    <saml:Issuer>https://engine.openconext.example.org/authentication/stepup/metadata</saml:Issuer>
    <samlp:Extensions>
        <gssp:UserAttributes xmlns:gssp="urn:mace:surf.nl:stepup:gssp-extensions"
                             xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                             xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <saml:Attribute NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"
                            Name="urn:mace:dir:attribute-def:mail">
                <saml:AttributeValue xsi:type="xs:string">j.doe@institution-a.example.org</saml:AttributeValue>
            </saml:Attribute>
            <saml:Attribute NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"
                            Name="urn:mace:terena.org:attribute-def:schacHomeOrganization">
                <saml:AttributeValue xsi:type="xs:string">institution-a.example.org</saml:AttributeValue>
            </saml:Attribute>
        </gssp:UserAttributes>
    </samlp:Extensions>
    <saml:Subject>
        <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:institution-a.example.org:u12345678</saml:NameID>
    </saml:Subject>
    <samlp:NameIDPolicy AllowCreate="true" />
    <samlp:RequestedAuthnContext Comparison="minimum">
        <saml:AuthnContextClassRef>http://stepup.example.org/assurance/sfo-level1.5</saml:AuthnContextClassRef>
    </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
```

Note that the `<saml:Subject>` contains the Subject identifier for the user for use in Stepup (`urn:collabLperson:...`), while the Subject identifier for the fallback GSSP is passed separately in the  `UserAttributes` extension (using "urn:mace:dir:attribute-def:mail" as the attribute name).

## Backwards compatibility

The `UserAttributes` extension is ignored by versions of the Stepup-Gateway that do not support GSSP Fallback. Including it in AuthnRequests sent to an older Gateway is safe and will not cause errors. Additional attributes in the extension UserExtension are ignored.
