# SSO on Second Factor Authentications

In Stepup-Gateway 4.2.0 the possibility to have single-sign-on (SSO) on the second factor was introduced. SSO works by storing a cookie in the user's browser after they have successfully with a second factor. Then, on a successive authentication that requires 2FA, when a valid SSO cookie is presented and several other policy checks are passed, the user is not asked for the second factor and is authenticated immediately. You can see the SSO cookie as a temporary second factor bearer token 

The SSO feature of the gateway does not affect authentication with the first factor, the first factor authentication is always forwarded to the [Remote IdP](SAMLProxy.md#remote-idp), if a first factor authentication is required.

For a description of the Middleware configuration see: https://github.com/OpenConext/Stepup-Middleware/blob/develop/docs/sso-on-2fa.md

There are two related SSO actions: setting/updating an SSO cookie and authenticating with an SSO cookie.

An SSO cookie is set if and only if all the following conditions are met:
- SSO is enabled for the institution by setting `sso_on_2fa` to true in the institution configuration
- The service provider configuration has `set_sso_cookie_on_2fa` set to true
- The second factor authentication is successful

Authenticating with an SSO cookie is only possible if and only is all the following conditions are met:
- SSO is enabled for the institution by setting `sso_on_2fa` to true in the institution configuration
- The service provider configuration has `allow_sso_on_2fa` set to true
- The SAML AuthnRequest does not have the ForceAuthn attribute set to true
- The SSO cookie is present and is valid
- The user id in the SSO cookie matches that of the user being authenticated
- The LoA of the SSO cookie is equal to or higher than the LoA required for the authentication

## The cookie
The cookie contains the following information:

| __Parameter name__          | __Description__                                            |
|-----------------------------|------------------------------------------------------------|
| `Second Factor Identifier`  | The identifier of the second factor that was authenticated |
| `Identifier`                | The IdentityId of the user                                 |
| `LoA`                       | The LoA of the second factor                               |
| `Timestamp`                 | The time at which the second factor was authenticated      |

The parameters in the cookie are used during authentication to:
- `Identifier`: To verify that the SSO was issued to the user currently being authenticated
- `LoA`: To verify that the LoA requirement of the current authentication can be met by the SSO cookie
- `Timestamp`: To verify that the SSO cookie is not expired (i.e. `sso_cookie_lifetime` has not passed)
- `Second Factor Identifier`: To verify that the second factor is still active (i.e. it was not revoked)

## Configuration options
When using SSO on second factor authentications (SSO on 2FA), you must configure the parameters below. The configuration options are configured in `config/legacy/parameters.yaml`

| __Parameter name__    | __Description__                                                                                             | __Data type__                                          |
|-----------------------|-------------------------------------------------------------------------------------------------------------|--------------------------------------------------------|
| `sso_cookie_lifetime` | The lifetime of the SSO cookie lifetime in seconds, cookies older than this lifetime are considered invalid | `integer`                                              |
| `sso_cookie_type`     | Is the SSO on second factor authentications cookie persistent or a session cookie                           | `string` `enum` possible values: (persistent, session) |
| `sso_cookie_name`     | The name used for the sso on second factor authentication cookie                                            | `string`                                               |
| `sso_encryption_key`  | The encryption key used to encrypt/decrypt and authenticate the cookie contents, must be 64 hex digits. | `string containing hex character`                      |

## Security

The cookie value contains sensitive data. Having the value of valid cookie is equivalent to having access to the user's second factor. SSO is a trade-off between security and convenience. When considering the risk of stealing an SSO cookie, consider that when an attacker is able to do that, they are likely able to perform actions with a higher impact than stealing this cookie. However, SSO does defeat user-presence detection. We put some security controls in place that limit the impact of stealing an SSO cookie and that allow SSO to be disabled in specific cases, to allow services to force a 2nd factor authentication when the risk of SSO is considered too high.

Measures:
- The cookie is only valid for a limited time, after which it expires. The means that a stolen cookie can only be used for a limited time. The lifetime of the cookie is configured globally with the `sso_cookie_lifetime` parameter.
- The cookie is only valid for a specific second factor. If this second factor is revoked, the cookie is no longer valid. This means that when a stolen cookie is detected or suspected, the user's token can be revoked.
- SSO can be disabled per SP. This allows that for SP where detecting user presence is important, SSO can be disabled. An SP can also disable SSO by setting the ForceAuthn attribute in the AuthnRequest to true. This will force the user to authenticate with the second factor. When using this option care must be taken that the integrity of the ForceAuthn attribute is protected. Otherwise, an attacker can set the ForceAuthn attribute to false and bypass the second factor authentication.
- SSO can be disabled per institution.

### SSO Cookie integrity and confidentiality

To protect the integrity and confidentiality of the cookie value, it is encrypted and authenticated using the `sso_encryption_key` that is stored in the gateway configuration. This is a 256-bit symmetric key. We use the [Paragonie Halite library](https://paragonie.com/project/halite) for encrypting and authenticating the cookie with this key. Halite uses XSalsa20 for encryption and BLAKE2b for message Authentication (MAC). The keys used for encryption and message authentication are derived from the secret key using a HKDF with a random a salt. This means that learning either derived key cannot lead to learning the other derived key, or the secret key input in the HKDF. Encrypting many messages using the same secret key is not a problem in this design.
 
  If your encryption requirements differ from ours, you can provide a different encryption method by implementing a different `Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto\CryptoHelperInterface` See [CookieValue](https://github.com/OpenConext/Stepup-Gateway/blob/3c3149b0e68daa1abcdf9a8e6009667d470c8d2d/src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php) for details

## References
See: [the corresponding Middleware docs](https://github.com/OpenConext/Stepup-Middleware/blob/develop/docs/sso-on-2fa.md)
