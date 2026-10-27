# Functional testing

The functional tests exercise the Gateway at the HTTP level, in-process: PHPUnit and Symfony's `WebTestCase` send real
HTTP requests (SAML AuthnRequests, SAML Responses, form posts) to the Symfony kernel and assert on the HTTP responses.
There is no browser, web server, MariaDB or other devconf container involved, which makes them fast and easy to debug
with a normal PHPUnit/IDE setup.

They complement (and for now do not replace) the Behat tests in `tests/features`, which run against a full devconf.

This document is for developers who want to add or change functional tests. For running the tests, see the
[README](../README.md#running-the-functional-tests).

## Contents

- [How it works](#how-it-works)
- [Directory layout](#directory-layout)
- [Writing a test](#writing-a-test)
- [Fixtures](#fixtures)
- [Capturing fixtures from devconf](#capturing-fixtures-from-devconf)
- [Re-signing captured responses](#re-signing-captured-responses)
- [Gotchas and limitations](#gotchas-and-limitations)

## How it works

### The `functional` Symfony environment

The tests run in a dedicated Symfony environment, `functional` (selected with `APP_ENV=functional` in
`ci/qa/phpunit-functional.xml`). It is separate from `test` (unit tests) and `smoketest` (Behat). Its configuration is:

| File | Purpose |
|------|---------|
| `config/packages/functional/framework.yaml` | Enables `framework.test`, mock file sessions, and imports `config/services_test.yaml` (Yubikey/SMS test doubles, dummy SSO cookie crypto) |
| `config/packages/functional/doctrine.yaml` | In-memory SQLite for both the `gateway` and `deploy` connections |
| `config/packages/functional/monolog.yaml` | Logs to `var/log/functional.log` instead of stderr, so test output stays readable |
| `config/packages/functional/{twig,nelmio_security}.yaml` | Strict Twig variables, CSP disabled |
| `config/services_functional.yaml` | Points all SAML key parameters (`saml_*`, `gssp_*`) at `tests/Functional/Fixtures/devconf/keys/`, instead of `/config/gateway/...` in the container |

The environment is not in debug mode, so the container is cached. **After changing configuration, remove
`var/cache/functional`** (`ci/qa/phpunit-functional` does this for you).

### Database

The Gateway only reads from its database, so the tests use an in-memory SQLite database that is created for every test:

1. `GatewayWebTestCase::setUp()` boots the kernel and `DevconfFixtureLoader` creates the schema from the Doctrine entity
   metadata (`SchemaTool`).
2. It then loads `Fixtures/devconf/database.sql`: the data captured from the devconf Gateway database (SPs, institution
   configuration, whitelist and registered second factors).

The database lives in the kernel, so the test client has `disableReboot()` called: the same kernel (and database) is used
for all requests of a test. Tests do not modify the database; they use the users and SPs that exist in devconf.

### SAML messages

The tests use real messages captured from devconf, signed with the devconf keys:

- **AuthnRequests** (SP to Gateway) are captured and replayed as is. They are signed (HTTP-Redirect binding), so they
  cannot be modified. The signature and `Destination` are bound to the devconf host name, which is why tests request
  `https://gateway.dev.openconext.local/...` with `HTTPS=on`.
- **Responses** (IdP or GSSP to Gateway) can not be replayed as captured: they answer the (random) request ID of the
  AuthnRequest the Gateway sent, and have a limited validity. Tests make them valid for the actual request with the
  [`SamlResponseResigner`](#re-signing-captured-responses).

### Time

Time is not mocked. The AuthnRequest fixtures are not time checked by the Gateway, and the resigner makes responses valid
for the current time. The Gateway does use `new DateTime('now')` in several places, so mocking time would require a clock
service in the application. Add it when a test needs it.

## Directory layout

```
tests/Functional/
├── Gateway/                  Test classes (SfoFlowTest, SsoFlowTest, GsspFlowTest)
├── Support/
│   ├── GatewayWebTestCase.php        Base test case (client, database, fixtures)
│   ├── Database/DevconfFixtureLoader.php   Creates the schema and loads database.sql
│   ├── Database/FixtureBuilder.php   Helpers for inserting extra data (not needed for devconf data)
│   └── Saml/
│       ├── SamlFixtureLoader.php     Loads SAML fixture files
│       ├── SamlRequestParser.php     Reads the AuthnRequest the Gateway sends (e.g. from a redirect Location)
│       ├── SamlResponseParser.php    Reads the SAML Response the Gateway returns
│       └── SamlResponseResigner.php  Makes a captured Response valid for a new request
├── Fixtures/devconf/         Everything captured from devconf
│   ├── database.sql
│   ├── keys/                 Certificates and private keys (<name>.crt / <name>.key)
│   └── saml/
│       ├── authn-requests/   *.php fixtures
│       └── responses/        *.php fixtures
└── bin/
    ├── capture-devconf-fixture   Capture database and keys
    └── convert-saml-to-fixture   Convert a captured SAML message to a fixture file
ci/qa/phpunit-functional(.xml)    Test runner and PHPUnit configuration
```

## Writing a test

Extend `GatewayWebTestCase`. It provides:

- `$this->client`: the `KernelBrowser`
- `$this->samlFixtures`: `loadRedirectAuthnRequest()`, `loadPostResponse()`, ...
- `$this->samlResigner`: re-signs captured responses
- `getSamlResponseFromCurrentPage()`: parses the `SAMLResponse` of the auto-submit form the Gateway renders (returns a
  `SamlResponseParser` with status, audience, NameID, signed, ...) and `getFormAction()`: the ACS URL of that form.

A test follows the flow like a browser would. This is a condensed version of a test in `GsspFlowTest`:

```php
// 1. The SP sends a captured AuthnRequest
$query = $this->samlFixtures->loadRedirectAuthnRequest('sfo-redirect-jane-a1-loa2');
$this->client->request('GET', 'https://gateway.dev.openconext.local/second-factor-only/single-sign-on?' . $query,
    server: ['HTTPS' => 'on']);

// 2. Follow redirects until the Gateway redirects to the remote party, and read the AuthnRequest it sent
$gatewayRequest = SamlRequestParser::fromRedirectUrl($this->client->getResponse()->headers->get('Location'));

// 3. Answer as the remote party: take the captured response, and make it valid for the Gateway's request
$this->client->request('POST', 'https://gateway.dev.openconext.local/gssp/demo_gssp/consume-assertion', [
    'SAMLResponse' => $this->samlResigner->resign(
        $this->samlFixtures->loadPostResponse('demo-gssp-response-jane-a1'),
        $gatewayRequest
    ),
], server: ['HTTPS' => 'on']);

// 4. Assert on the SAML response the Gateway sends to the SP
$samlResponse = $this->getSamlResponseFromCurrentPage();
```

See `SfoFlowTest`, `GsspFlowTest` and `SsoFlowTest` for complete examples, including negative tests (a replayed
response, and a response to another request).

Tips:

- Use users and SPs from `database.sql`. To see what is available look at the SQL (or the devconf configuration):
  e.g. `joe-a-yk` (Yubikey) and `jane-a1` (Demo GSSP) of `institution-a.example.com`.
- The Yubikey and SMS services are test doubles (`config/services_test.yaml`): any OTP is accepted.
- To see what the Gateway did, read `var/log/functional.log`. For a failing request, log the response content or run
  with a breakpoint; the Gateway's error pages are rendered as normal HTML responses.
- The Gateway behaves differently for different failures: an invalid/expired assertion results in an error SAML response
  to the SP, but an unknown `InResponseTo` results in an exception (HTTP 500). Assert on what the Gateway really does.

## Fixtures

All fixtures are in `tests/Functional/Fixtures/devconf` and are captured from a running devconf. They are committed, so
running the tests does not require devconf.

### SAML fixture files

A SAML fixture is a PHP file that returns a string. This keeps the fixture executable and lets it carry documentation:
the generated header and a block comment with the decoded XML make it easy to see what a fixture contains.

- `saml/authn-requests/<name>.php` returns the HTTP-Redirect query string
  (`SAMLRequest=...&RelayState=...&SigAlg=...&Signature=...`).
- `saml/responses/<name>.php` returns the base64 encoded XML of a Response (the value of the `SAMLResponse` POST
  parameter).

`SamlFixtureLoader` loads them by name, without extension (`loadRedirectAuthnRequest('sso-redirect-loa2')`).
Name fixtures after what they contain, e.g. `sfo-redirect-jane-a1-loa2`, `ssp-response-joe-a-yk`.

Do not edit generated fixtures by hand (a signed request would not validate anymore). Capture a new one instead.

## Capturing fixtures from devconf

### Prerequisites

A running [devconf](https://github.com/OpenConext/OpenConext-devconf) (`OpenConext-devconf`, started with
`./start-dev-env.sh`) with the users and tokens the tests need (register tokens through Self-Service). Fixtures are
captured with the host names of devconf (`*.dev.openconext.local`), so the tests use these host names as well.

The current database capture was created by:
* bootstrapping a stepup devconf (i.e. only the admin user is active with a yubikey token) 
* Registering a yubikey token for joe-a-yk, vetted by admin
* Registering a Demo GSSP token for jane-a1, vetted by admin

### Database and keys

```
php tests/Functional/bin/capture-devconf-fixture /path/to/OpenConext-devconf database
php tests/Functional/bin/capture-devconf-fixture /path/to/OpenConext-devconf keys
```

- `database` runs `mysqldump` in the devconf `mariadb` container (data only), converts it to SQLite-compatible SQL and
  writes `Fixtures/devconf/database.sql`. The conversion adds column names to the inserts (the dump uses MySQL's column
  order, which differs from the SQLite schema created from the entities), converts MySQL string escapes, strips
  MariaDB directives and anonymizes the Yubikey identifiers. The schema is not part of the dump: it comes from the
  entities, so the Doctrine mapping and the devconf database must be in sync.
- `keys` copies the certificates and private keys used by the SPs, IdPs and GSSPs of devconf to `Fixtures/devconf/keys/`.
  The keys are the well known devconf development keys.

Capture the database again when you registered a new user, token or SP in devconf. Review the diff of
`database.sql`: all of it is committed.

### SAML messages

SAML messages are captured from the browser, while going through the flow in devconf:

1. Install a SAML inspector in the browser (e.g. SAML Tracer), and perform the authentication in devconf.
2. Copy the message:
   - **AuthnRequest (HTTP-Redirect):** the URL/query string of the request to the Gateway
     (`.../single-sign-on?SAMLRequest=...&SigAlg=...&Signature=...`). Copy the complete query string; the signature is
     over exactly these (encoded) parameters.
   - **Response (HTTP-POST):** the value of the `SAMLResponse` form parameter of the POST to the Gateway
     (e.g. `.../consume-assertion`), the base64 encoded XML.
3. Convert it to a fixture:

```
php tests/Functional/bin/convert-saml-to-fixture --auto "<captured message>" sfo-redirect-jane-a1-loa2
php tests/Functional/bin/convert-saml-to-fixture --file captured.txt ssp-response-jane-a1
```

   The type (`authn-request-redirect`, `authn-request-post`, `response-post`) is detected (or use `--type`) and the
   fixture is written to `Fixtures/devconf/saml/authn-requests` or `.../responses`. A leading `GET ... HTTP/1.1` is
   stripped. The fixture gets a header with a summary of the message (issuer, destination, NameID, requested LoA, ...)
   and the decoded XML.

Things to keep in mind when capturing:

- **Capture exactly the flow the test follows.** E.g. the LoA requested in the AuthnRequest decides if a second factor is
  required: `sso-redirect-no-loa` is answered without a second factor, because the SP's default LoA is LoA 1.
- An SSO AuthnRequest does not contain a user: the user comes from the IdP response. So one request can be used with
  responses of multiple users. An SFO AuthnRequest contains the NameID of the user and is user specific.
- Capture a Response of the user that the test uses (e.g. `ssp-response-joe-a-yk`). A GSSP response is not specific for
  the Gateway request, so it can be used with any flow of that user.
- The captured Response needs a signed *Assertion* (as sent by SimpleSAMLphp and the Demo GSSP), and its certificate must
  be in `Fixtures/devconf/keys` (see below).

## Re-signing captured responses

`SamlResponseResigner::resign($base64Response, SamlRequestParser $request)` returns a base64 Response that the Gateway
accepts as the answer to `$request`. It changes only:

- `InResponseTo` of the Response and the `SubjectConfirmationData`: set to the ID of the request;
- all timestamps (`IssueInstant`, `NotBefore`, `NotOnOrAfter`, `AuthnInstant`, `SessionNotOnOrAfter`), shifted so that the
  Response was issued now (the length of the validity windows is preserved);
- the `ds:Signature` of the Assertion.

The private key is looked up from the certificate in the Response's `ds:KeyInfo`: the resigner searches
`Fixtures/devconf/keys/*.crt` for that certificate, and uses the `.key` file with the same name. If a new remote party
(IdP or GSSP) is used, make sure its key pair is captured by `capture-devconf-fixture keys` (add it to the `$keySources`
list in the script if it is not there).

`SamlRequestParser::fromRedirectUrl($location)` decodes the AuthnRequest in a redirect the Gateway sends (for instance to
the IdP or to a GSSP), and gives the request ID (`getId()`).

To test the Gateway's rejection of a bad response, pass the original captured response to the Gateway (it is expired and
answers an unknown request), or resign it for a different request: `new SamlRequestParser('<samlp:AuthnRequest
xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="_other"/>')`.

## Gotchas and limitations

- Requires the PHP `pdo_sqlite` extension.
- After changing config in `config/packages/functional/` or `config/services_functional.yaml`, clear `var/cache/functional`.
- Captured fixtures contain issue times in the past. This is fine for AuthnRequests (the Gateway does not check them);
  Responses must be resigned.
- Fixtures reference the devconf host names and certificates. When devconf changes keys or host names, recapture the
  fixtures (database, keys and SAML messages).
- Time is not mocked, see [Time](#time).
- Only HTTP-Redirect AuthnRequests and HTTP-POST Responses have been used so far; HTTP-POST AuthnRequests
  (`loadPostAuthnRequest`) are supported by the loader but not yet exercised by tests.
