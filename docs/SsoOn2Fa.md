# SSO on Second Factor Authentications
When the feature is enabled for the institution, when using a MFA token we create a cookie containing information about the token that was used during MFA.
On every successive authentication presented to the Stepup-Gateway we check if the cookie contains a valid authentication for the specified LoA.
If the cookie meets the requirements, the authenticating user is not asked for the second factor authentication.
When the service requires MFA, this can still be forced regardless of the SSO cookie, by adding the ForceAuthn attribute on the AuthnRequest element.

## The cookie
The cookie contains several values, used to ascertain if SSO can be given. These values are:

| __Parameter name__               | __Description__                            |
|----------------------------------|--------------------------------------------|
| `Second Factor Identifier` | The identifier of the second factor token  |
| `Identifier`                     | IdentityId associated to the SecondFactor  |
| `LoA`                            | The LoA of the second factor               |
| `Timestamp`                      | The timestamp taken during authentication. |

The cookie is used to verify the SSO is issued to the correct identity (user). And to check if the LoA requirement is satisfied by the SSO cookie. 
The timestamp is used to verify if the cookie value did not expire. This is determined by adding the cookie expiration time (configured in `sso_cookie_lifetime` param) 
to the authentication timestamp found in the cookie. If those two exceed the current timestamp, the cookie is considered to be expired. Even tho the browser cookie itself 
might still be alive. 

The cookie lifetime can be configured with a grace period. This is useful when your setup include multiple StepUp gateways that are able to verify the cookie validity. 
In that case a (minor) time difference between the nodes can cause a false positive invalid cookie scenario. By adding the grace period, the end user is not directly
affected in a negative manner.

At this point this grace period is configured to be 60 seconds in the `gateway.service.sso_2fa_expiration_helper` service definition in `src/Surfnet/StepupGateway/GatewayBundle/Resources/config/services.yml`

The cookie value contains sensitive data, and its contents are authenticated and encrypted for that reason. We use the Paragonie Halite library for this. Halite uses XSalsa20 for encryption and BLAKE2b for message Authentication (MAC).

If your encryption requirements differ from ours, you can simply provide a different encryption method by implementing a different `Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto\CryptoHelperInterface`

See [CookieValue](https://github.com/OpenConext/Stepup-Gateway/blob/3c3149b0e68daa1abcdf9a8e6009667d470c8d2d/src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php) for details

## Configuration options
When using SSO on second factor authentications (SSO on 2FA), you are allowed to configure three configuration 
parameters. The configuration options are configured in `config/legacy/parameters.yaml`

| __Parameter name__    | __Description__                                                                        | __Data type__                                          |
|-----------------------|----------------------------------------------------------------------------------------|--------------------------------------------------------|
| `sso_cookie_lifetime` | The lifetime of the SSO on second factor authentications lifetime in seconds           | `integer`                                              |
| `sso_cookie_type`     | Is the SSO on second factor authentications cookie persistent or a session cookie?     | `string` `enum` possible values: (persistent, session) |
| `sso_cookie_name`     | The name used for the sso on second factor authentication cookie                       | `string`                                               |
| `sso_encryption_key`  | The encryption key used to encrypt/decrypt the cookie contents, must be 64 hex digits. | `string containing hex character`                      |
