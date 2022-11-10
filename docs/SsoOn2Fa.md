# SSO on Second Factor Authentications

## Configuration options
When using SSO on second factor authentications (SSO on 2FA), you are allowed to configure three configuration 
parameters. The configuration options are configured in `config/legacy/parameters.yanml`

| __Parameter name__    | __Description__                                                                          | __Data type__                                          |
|-----------------------|------------------------------------------------------------------------------------------|--------------------------------------------------------|
| `sso_cookie_lifetime` | The lifetime of the SSO on second factor authentications lifetime in seconds             | `integer`                                              |
| `sso_cookie_type`     | Is the SSO on second factor authentications cookie persistent or a session cookie?       | `string` `enum` possible values: (persistent, session) |
| `sso_cookie_name`     | The name used for the sso on second factor authentication cookie                         | `string`                                               |
| `sso_encryption_key`  | The encryption key used to encrypt/decrypt the cookie contents, should be 64 hex digits. | `string containing hex character`                      |
