# RFC Showing Service name during authentication

## Why?
To strengthen the defense against phishing and social engineering attacks, we want to display the name of the actual service the user is authenticating to (e.g., "eduVPN" or "XXX Grading system"), rather than the generic gateway name (e.g., "OpenConext").

Showing the service name provides the user with additional context to detect malicious activity through contextual mismatch. Crucially, this still relies on the user's ability to detect that something is wrong. 

It enhances security in two distinct places:

1. Contextual Verification in the Browser: By displaying the actual service name in the authentication interface in the user's browser, users can verify that the service they are authenticating to matches their expectations. This helps expose malicious links intended to hijack sessions (e.g., session pinning) and provides context when authenticating from embedded browsers or captive portals where standard URL bars are hidden.
2. Contextual Verification for Out-of-Band Methods (SMS & Tiqr): When a second factor is triggered on a separate device, including the service name in the SMS or push notification this helps users identify vishing (voice phishing) attempts like a fake helpdesk caller triggering a login to the HR system while claiming to help with a full mailbox. It allows users to make more informed decisions to reject unexpected authentication prompts, reducing the success rate of MFA fatigue (prompt bombing) attacks.

## Getting the service name
Stepup (i.e., the Stepup-Gateway) currently does not know the name of the service (i.e., SAML Service Provider, OIDC Relying Party, SFO Application) that the user is authenticating to.

Broadly speaking, we have to:
1. Get the name of the service that the user is authenticating to, the source of the service name will depend on how the authentication arrived at the Stepup-Gateway.
2. Get the service name to where it must be displayed to the user. This could be in the Stepup-Gateway itself for the built-in authentication methods (SMS and Yubikey OTP) or in the GSSPs (Tiqr, Webauthn, AzureMFA).
3. Display the service name to the user during authentication. How to achieve this will depend on the authentication method.

There are several ways to initiate a second factor authentication the the Stepup-gateway:
1. A SAML Service Provider that is [directly connected](../docs/SAMLProxy.md) to the Stepup-Gateway. The Stepup-Gateway will handle the 1st and 2nd (when required) factor authentication. These service providers are [configured in Stepup-Middleware](https://github.com/OpenConext/Stepup-Middleware/blob/main/docs/MiddlewareConfiguration.md#service-providers). Stepup-SelfService and Stepup-RA are [commonly configured this way](https://github.com/OpenConext/OpenConext-devconf/blob/main/stepup/middleware/middleware-config.json#L46-L74). The "test a token" functionality in Stepup-SelfService and the proof-of-possesion during vetting in the RA also follow this route.
2. An [SFO Application](../docs/SFO.md) that is connected to the Stepup-Gateway. There are e.g. Microsoft ADFS using the [Stepup MFA extension](https://github.com/SURFnet/ADFS-MFA-SAML2.0-Extension), [OpenConext-engineblock using [Stepup](https://github.com/OpenConext/OpenConext-engineblock/blob/main/docs/stepup_callout.md). SFO Applications are configured in the [same middleware configuration file](https://github.com/OpenConext/Stepup-Middleware/blob/main/docs/MiddlewareConfiguration.md#service-providers) as the SAML Service providers. 

For some applications storing the service name in the middleware configuration will be enough. But for an SFO application like OpenConext-engine—that is an authentication proxy to thousands of services—we want to show the name of the service that the user is authenticating to using OpenConext-engine instead of the name of the proxy (e.g. "OpenConext-engine"). To handle both cases we need to store a (default) service name in the middleware configuration and implement a way to override this name by the service during authentication.

### Store the name of the service in the middleware configuration

Extend the middleware configuration with a new field `service_name` that can be used to store the default service name. This field is optional and can be omitted. If set it must be a map of locale to service name.

'''json
{
...
  "gateway": {
    "service_providers": [
      {
        "entity_id": "https://ra.dev.openconext.local/authentication/metadata",
        "public_key": "MII...",
        "acs": [
            "https://ra.dev.openconext.local/authentication/consume-assertion"
        ],
        "loa": {
            "__default__": "http://dev.openconext.local/assurance/loa1"
        },
        "assertion_encryption_enabled": false,
        "second_factor_only": false,
        "second_factor_only_nameid_patterns": [],
        "blacklisted_encryption_algorithms": []
        "service_name": {
          "en_GB": "Registration Portal",
          "nl_NL": "Registratie Portaal"
        }
      },
      {
        "entity_id": "https://selfservice.dev.openconext.local/authentication/metadata",
        ...
      }
    ]
  }
}
'''

### Get the service name from the (SFO) SP during authentication

All authentication requests to the Stepup-Gateway are signed SAML 2.0 AuthnRequests. This means that information can be reliably transferred from a SP to the Stepup-Gateway in the SAML AuthnRequest. For the [GSSP Fallback](../docs/GSSPFallback.md) the SAML AuthnRequest is already used for a similar purpose by using a SAML Extensions to pass the required [User Attributes](../docs/UserAttributes.md).

For getting the service name from the (SFO) SP during authentication we introduce a new SAML extension based on the existing [mdui](https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/sstc-saml-metadata-ui-v1.0.html) SAML Extensions that are already being used in the Metadata of OpenConext.

From the [mdui XML Scheme](https://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/os/xsd/sstc-saml-metadata-ui-v1.0.xsd):

```XML

<schema
        targetNamespace="urn:oasis:names:tc:SAML:metadata:ui"
        xmlns="http://www.w3.org/2001/XMLSchema"
        xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
        xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">
  <!-- ... -->
  <element name="UIInfo" type="mdui:UIInfoType" />
  <complexType name="UIInfoType">
    <choice minOccurs="0" maxOccurs="unbounded">
      <element ref="mdui:DisplayName"/>
      <element ref="mdui:Description"/>
      <element ref="mdui:Keywords"/>
      <element ref="mdui:Logo"/>
      <element ref="mdui:InformationURL"/>
      <element ref="mdui:PrivacyStatementURL"/>
      <any namespace="##other" processContents="lax"/>
    </choice>
  </complexType>

  <element name="DisplayName" type="md:localizedNameType"/>
  <!-- ... -->
</schema>
```

The `md:LocalizedNameType` type is defined in the [SAML Metadata XML Schema](https://docs.oasis-open.org/security/saml/v2.0/saml-schema-metadata-2.0.xsd) as:

```XML
<schema
    targetNamespace="urn:oasis:names:tc:SAML:2.0:metadata"
    xmlns="http://www.w3.org/2001/XMLSchema">
  <!-- ... -->
  
  <complexType name="localizedNameType">
    <simpleContent>
      <extension base="string">
        <attribute ref="xml:lang" use="required"/>
      </extension>
    </simpleContent>
  </complexType>

  <!-- ... -->
  
</schema>
```

This means that we can add an `mdui:UIInfo` element to the SAML Extensions in an AuthnRequest. For communicating the service name we choose the `mdui:DisplayName` element. The `xml:lang` is used to specify the language of the display name. E.g.:

```XML
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
      <mdui:UIInfo>
        <mdui:DisplayName xml:lang="en">Online learning environment</mdui:DisplayName>
        <mdui:DisplayName xml:lang="nl">Electronische leeromgeving</mdui:DisplayName>
      </mdui:UIInfo>
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

A SAML `Extensions` element is optional and can contain multiple elements, in any order. A Authentication Request can contain a UserInfo element besides the Extensions element.

Each component (Stepup-Gateway, Stepup-tiqr, other GSSPs) that can use the UIInfo:
- MUST not fail if the UIInfo element is missing.
- MUST not fail if the UIInfo element does not contain any DisplayName elements.
- MUST not fail if the UIInfo element contains other elements besides DisplayName elements.
- MUST ignore other extension elements, i.e. use the first DisplayName element in the Extensions.

## OpenConext-engineblock

Introduce a [feature flag](https://github.com/OpenConext/OpenConext-engineblock/blob/main/config/packages/parameters.yml.dist#L222-L239) in engineblock to enable/disable adding the UIInfo element to [stepup callouts](https://github.com/OpenConext/OpenConext-engineblock/blob/main/docs/stepup_callout.md)): `feature_stepup_send_service_name`

When enabled: For each SAML AuthnRequest from OpenConext-engineblock for the Stepup callout, engineblock MUST add the `mdui:UIInfo` element to the SAML `Extensions` element in the AuthnRequest with a `md:DisplayName` for each available language. Use the service name from the `name:nl` and `name:en` fields from the SP entity (saml20_sp) or RP entity (oidc10_rp). These entities are stored in OpenConext-manage and [pushed to engine via an API](https://github.com/OpenConext/OpenConext-engineblock/blob/main/docs/metadata_push.md).

 ## Stepup-Gateway

Taking a service name from the SAML AuthnRequest by the Stepup-Gateway MUST be put behind a feature flag in the Stepup-Gateway's parameter.yaml: `enable_service_name_from_saml_authnrequest`. This feature flag is disabled by default and bust be set to `true` in the Stepup-Gateway configuration to enable dynamicaly getting the service name from the SAML AuthnRequest.

We do not want all service providers that are connected to the stepup gateway to be allowed to send a service name. This would e.g. allow a (compromised) SFO service to override the service name and to impersonate each other's services or inject malicious instructions to the user in the Stepup-Gateway or GSSP UI.

For this reason the Stepup-gateway MUST only take the service name from the md:DisplayName element when there is no service_name configured in the middleware configuration for that service.

| Middleware Configuration service_name | SAML AuthnRequest md:DisplayName | Use               |
|---------------------------------------|----------------------------------|-------------------|
| No                                    | Yes                              | md:DisplayName    |
| Yes                                   | Yes                              | service_name      |
| No                                    | No                               | current behaviour |
| Yes                                   | No                               | service_name      |

The md:DisplayName can be sent both to the SFO endpoint and to the normal authentication endpoint.

# Restrictions on the service name
We want to (potentially) use it in SMS messages, Tiqr Push Notification and the web UI.
For an SMS message, the maximum length is 160 characters. This drops to 70 characters if the message contains characters outside the 7-bit GSM alphabet. 

MAX_CHARACTERS = 40
Rationale: 
- leaves 30 characters for an SMS message + challenge
- Should be enough for the service name
- Should not break to Web UI (too much)

Service name processing:
- The service name MUST be truncated to MAX_CHARACTERS-1. When truncated, append an ellipsis ("…" U+2026).
- Whitespace MUST be removed from the beginning and end of the service name.
- Consecutive whitespace characters MUST be replaced with a single space.
- Control and Format characters MUST be removed from the service name.

### GSSP Authentication

The Stepup-Gateway must add the service name to the SAML AuthnRequest to the GSSP when either the service_name or md:DisplayName is available in the authentication. It MUST NOT send the md:DisplayName to the GSSP when neither the service_name nor md:DisplayName is available.

The display of the service name is done by the GSSP, in a GSSP specific manner.

#### Tiqr

Set the service name when sending the push notification using [Tiqr_Service::sendAuthNotification()](https://github.com/Tiqr/tiqr-server-libphp/blob/develop/library/tiqr/Tiqr/Service.php#L337)

Show the service name in the Stepup-tiqr GSSP UI.

### SMS authentication

The SMS authentication method is build into the Stepup-Gateway. The SMS message is currently: 

```XML
<source>gateway.second_factor.sms.challenge_body</source>
<target>Your SMS code: %challenge%</target>

<source>gateway.second_factor.sms.challenge_body</source>
<target>Je sms-code: %challenge%</target>
```

TODO: Decide if we want to add the service name to the SMS message (now).
When service name is avaiable we could change it to something like: `Your SMS code for %service_name%: %challenge%` and `Je sms-code voor %service_name%: %challenge%`

Show the service name in the UI.

### Yubikey OTP authentication

TODO: Decide if we want to add the service name to the Yubikey OTP screen (`verify-second-factor/sso/yubikey`) displayed to the user by the Stepup-Gateway.

Show the service name in the UI.

### Webauthn authentication

Show the service name in the Stepup-WebAuthn GSSP UI.

### AzureMFA authentication

It is not possible to add the service name to the AzureMFA authentication screen, because there is no interaction with the user before the authentication is triggered and there is no mechanism to send the service name to EntraID.
