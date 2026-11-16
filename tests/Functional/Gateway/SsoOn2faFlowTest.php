<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Gateway;

use DateTimeImmutable;
use DateTimeZone;
use SAML2\Constants;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Crypto\HaliteCryptoHelper;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\Configuration;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;
use Surfnet\StepupGateway\Tests\Functional\Support\GatewayWebTestCase;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlRequestParser;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\HttpFoundation\Cookie as HttpCookie;

/**
 * Functional tests for the SSO-on-2FA cookie: a persistent, encrypted and signed cookie the Gateway sets on a successful
 * response when the SP is allowed to set it (set_sso_cookie_on_2fa) and the institution has SSO on 2FA enabled.
 * It replaces second factor on later requests when the SP allows it (allow_sso_on_2fa), unless
 * ForceAuthn is requested or the cookie is invalid.
 *
 * The functional environment uses the real Halite cookie crypto (see config/services_functional.yaml, which
 * overrides the dummy of services_test.yaml), so these tests decrypt the Set-Cookie header of the Gateway's
 * responses with the same helper, and forge cookie values with it. The KernelBrowser cookie jar carries the
 * cookie between the requests of a test.
 *
 * Test data comes from tests/Functional/Fixtures/devconf/database.sql (captured from devconf):
 * - SPs: default-sp (SSO) and second-sp (SFO) have allow_sso_on_2fa + set_sso_cookie_on_2fa enabled, third-sp and
 *   the RA (ra.dev.openconext.local/authentication/metadata) have both disabled
 * - Institutions: institution-a.example.com and institution-b.example.com have sso_on2fa_enabled;
 *   institution-d.example.com has it disabled (user joe-d-yk, see INSTITUTION_WITHOUT_SSO_ON_2FA_*)
 * - Users: joe-a-yk (vetted Yubikey, LoA 3) and joe-a-sms (vetted SMS, LoA 2) of institution-a.example.com, and
 *   joe-b1 of institution-b.example.com (self-asserted Demo GSSP token)
 *
 * The second_factor_id and second_factor_identifier of the users' tokens are looked up at runtime by NameID +
 * token type (see secondFactorOf()): the IDs are GUIDs and the Yubikey identifiers are renumbered when database.sql
 * is captured, so both change when the devconf database is recreated, while the NameIDs are stable.
 *
 * The first factor is the SimpleSAMLphp IdP of devconf. Its response is captured from devconf, and made valid for
 * the AuthnRequest sent by the gateway by the SamlResponseResigner.
 */
class SsoOn2faFlowTest extends GatewayWebTestCase
{
    private const GATEWAY_SSO_URL = 'https://gateway.dev.openconext.local/authentication/single-sign-on';
    private const GATEWAY_SFO_URL = 'https://gateway.dev.openconext.local/second-factor-only/single-sign-on';
    private const GATEWAY_ACS_URL = 'https://gateway.dev.openconext.local/authentication/consume-assertion';
    private const IDP_SSO_URL = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/idp/singleSignOnService';

    private const DEFAULT_SP_ENTITY_ID = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/default-sp';
    private const DEFAULT_SP_ACS_URL = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp';
    private const SECOND_SP_ENTITY_ID = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/second-sp';
    private const SECOND_SP_ACS_URL = 'https://ssp.dev.openconext.local/simplesaml/module.php/debugsp/acs/second-sp';
    private const THIRD_SP_ENTITY_ID = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/third-sp';
    private const THIRD_SP_ACS_URL = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/third-sp';
    private const RA_ENTITY_ID = 'https://ra.dev.openconext.local/authentication/metadata';
    private const RA_ACS_URL = 'https://ra.dev.openconext.local/authentication/consume-assertion';

    /** The cookie name and lifetime, config/openconext/parameters.yaml */
    private const SSO_COOKIE_NAME = 'stepup-gateway_sso-on-second-factor-authentication';
    private const SSO_COOKIE_LIFETIME = 28800;

    private const JOE_A_YK_NAME_ID = 'urn:collab:person:institution-a.example.com:joe-a-yk';
    private const JOE_A_SMS_NAME_ID = 'urn:collab:person:institution-a.example.com:joe-a-sms';
    private const JOE_B1_NAME_ID = 'urn:collab:person:institution-b.example.com:joe-b1';

    /**
     * A user of an institution with sso_on2fa_enabled = 0, for the institution gate on write. The devssp test
     * account joe-d-yk has schacHomeOrganization Institution-D.EXAMPLE.COM; the middleware normalizes that to
     * institution-d.example.com, which is whitelisted and has no sso_on_2fa in the middleware institution
     * configuration (so sso_on2fa_enabled = 0 in the captured database.sql).
     *
     * The test is skipped until the devconf captures for this user exist: the second_factor row in database.sql
     * (recaptured with tests/Functional/bin/capture-devconf-fixture ... database) and the IdP response fixture
     * ssp-response-joe-d-yk (captured with SAML Tracer + convert-saml-to-fixture).
     */
    private const INSTITUTION_WITHOUT_SSO_ON_2FA_NAME_ID = 'urn:collab:person:Institution-D.EXAMPLE.COM:joe-d-yk';
    private const INSTITUTION_WITHOUT_SSO_ON_2FA_IDP_RESPONSE = 'ssp-response-joe-d-yk';

    private const LOA3 = 'http://dev.openconext.local/assurance/loa3';

    /** The ID of the SP AuthnRequest currently being processed */
    private string $spRequestId = '';

    // ----- Writing the cookie -----

    /**
     * SSO at default-sp (allow + set enabled), second factor Yubikey of joe-a-yk: the response to the SP carries the
     * Set-Cookie header. The cookie value is encrypted with the configured key (Halite), and decrypts to the second
     * factor, NameID, LoA and a fresh authentication time of joe-a-yk. The cookie attributes are persistent,
     * path=/, Secure, HttpOnly, SameSite=None, expiring after the configured lifetime.
     */
    public function testSsoAtDefaultSpWritesEncryptedSsoCookieAfterSecondFactorAuthentication(): void
    {
        $cookieValue = $this->authenticateAtDefaultSpAndWriteCookie('ssp-response-joe-a-yk');

        $this->assertSuccessfulSsoResponse(
            self::DEFAULT_SP_ENTITY_ID,
            self::DEFAULT_SP_ACS_URL,
            self::JOE_A_YK_NAME_ID,
            'gateway-sso-response-joe-a-yk-loa3'
        );

        $this->assertSame($this->secondFactorIdOf(self::JOE_A_YK_NAME_ID, 'yubikey'), $cookieValue->secondFactorId());
        $this->assertSame(self::JOE_A_YK_NAME_ID, $cookieValue->getIdentityId());
        $this->assertSame(3.0, $cookieValue->getLoa(), 'The Yubikey of joe-a-yk gives LoA 3');
        $this->assertFreshAuthenticationTime($cookieValue->authenticationTime());
    }

    /**
     * SFO at second-sp (allow + set enabled), second factor Yubikey of joe-a-yk: the SFO response to the SP also
     * carries the Set-Cookie header (SecondFactorOnlyController writes the cookie as well).
     */
    public function testSfoAtSecondSpWritesEncryptedSsoCookieAfterSecondFactorAuthentication(): void
    {
        $query = $this->samlFixtures->loadRedirectAuthnRequest('sfo-redirect-joe-a-yk-loa2');
        $this->spRequestId = SamlRequestParser::fromRedirectUrl($query)->getId();
        $this->client->request('GET', self::GATEWAY_SFO_URL . '?' . $query, server: ['HTTPS' => 'on']);

        $this->followRedirects();
        $this->submitYubikeyOtp($this->secondFactorIdentifierOf(self::JOE_A_YK_NAME_ID, 'yubikey'));
        $this->followRedirects();

        $this->assertSuccessfulSsoResponse(
            self::SECOND_SP_ENTITY_ID,
            self::SECOND_SP_ACS_URL,
            self::JOE_A_YK_NAME_ID,
            'gateway-sfo-response-joe-a-yk-loa3-redirect'
        );

        $this->assertSsoCookieIsInClientJar();
        $cookie = $this->assertSsoCookieIsSetInCurrentResponse();
        $this->assertSsoCookieAttributes($cookie);
        $cookieValue = $this->decryptCookieValue($cookie->getValue());
        $this->assertSame($this->secondFactorIdOf(self::JOE_A_YK_NAME_ID, 'yubikey'), $cookieValue->secondFactorId());
        $this->assertSame(self::JOE_A_YK_NAME_ID, $cookieValue->getIdentityId());
        $this->assertSame(3.0, $cookieValue->getLoa());
        $this->assertFreshAuthenticationTime($cookieValue->authenticationTime());
    }

    /**
     * SSO at third-sp (allow_sso_on_2fa and set_sso_cookie_on_2fa both false): after the second factor authentication
     * the response to the SP does not carry the Set-Cookie header.
     */
    public function testSsoAtThirdSpDoesNotWriteSsoCookie(): void
    {
        $this->startSsoAndAuthenticateAtIdp('sso-redirect-third-sp-loa2-1', 'ssp-response-joe-a-yk');
        $this->followRedirects();
        $this->submitYubikeyOtp($this->secondFactorIdentifierOf(self::JOE_A_YK_NAME_ID, 'yubikey'));
        $this->followRedirects();

        $this->assertSuccessfulSsoResponse(self::THIRD_SP_ENTITY_ID, self::THIRD_SP_ACS_URL, self::JOE_A_YK_NAME_ID, null);
        $this->assertSsoCookieIsNotSetInCurrentResponse();
    }

    /**
     * Security pin: SSO at the RA (allow + set false, it always requests LoA 3): the response to the RA after the
     * Yubikey second factor of joe-a-yk does not carry the Set-Cookie header. No SSO cookie may be written during
     * RA vetting.
     */
    public function testSsoAtRaDoesNotWriteSsoCookie(): void
    {
        $this->startSsoAndAuthenticateAtIdp('sso-redirect-ra-loa3-1', 'ssp-response-joe-a-yk');
        $this->followRedirects();
        $this->submitYubikeyOtp($this->secondFactorIdentifierOf(self::JOE_A_YK_NAME_ID, 'yubikey'));
        $this->followRedirects();

        $this->assertSuccessfulSsoResponse(self::RA_ENTITY_ID, self::RA_ACS_URL, self::JOE_A_YK_NAME_ID, null);
        $this->assertSsoCookieIsNotSetInCurrentResponse();
    }

    /**
     * The institution gate on write, using the devconf data as captured (tests do not modify the database): joe-d-yk
     * belongs to institution-d.example.com, which has sso_on2fa_enabled = 0 in database.sql. Although default-sp
     * allows setting the cookie, no Set-Cookie is written after his Yubikey second factor.
     *
     * Skipped until the devconf captures for joe-d-yk exist (see the constants above).
     */
    public function testSsoDoesNotWriteSsoCookieWhenTheInstitutionHasSsoOn2faDisabled(): void
    {
        $this->skipIfUserIsNotCapturedFromDevconf(
            'joe-d-yk',
            self::INSTITUTION_WITHOUT_SSO_ON_2FA_NAME_ID,
            self::INSTITUTION_WITHOUT_SSO_ON_2FA_IDP_RESPONSE
        );

        $this->startSsoAndAuthenticateAtIdp('sso-redirect-loa2-1', self::INSTITUTION_WITHOUT_SSO_ON_2FA_IDP_RESPONSE);
        $this->followRedirects();
        $this->submitYubikeyOtp($this->secondFactorIdentifierOf(self::INSTITUTION_WITHOUT_SSO_ON_2FA_NAME_ID, 'yubikey'));
        $this->followRedirects();

        $this->assertSuccessfulSsoResponse(
            self::DEFAULT_SP_ENTITY_ID,
            self::DEFAULT_SP_ACS_URL,
            self::INSTITUTION_WITHOUT_SSO_ON_2FA_NAME_ID,
            null
        );
        $this->assertSsoCookieIsNotSetInCurrentResponse();
    }

    // ----- Using the cookie -----

    /**
     * Two SSO requests at default-sp: the first writes the cookie (after the Yubikey second factor), the second
     * (sso-redirect-loa2-2) sends it automatically in the client cookie jar. After the IdP response of the second
     * request the Gateway goes straight to the SP response: no second factor verification, and the cookie is not
     * rewritten (the authentication was verified by the cookie).
     */
    public function testSecondSsoRequestSkipsSecondFactorUsingTheSsoCookie(): void
    {
        $cookieValue = $this->authenticateAtDefaultSpAndWriteCookie('ssp-response-joe-a-yk');
        $this->assertSame(3.0, $cookieValue->getLoa());
        $this->assertSuccessfulSsoResponse(
            self::DEFAULT_SP_ENTITY_ID,
            self::DEFAULT_SP_ACS_URL,
            self::JOE_A_YK_NAME_ID,
            'gateway-sso-response-joe-a-yk-loa3'
        );

        // Second SSO request: the client sends the cookie with the AuthnRequest and with the consume-assertion POST
        $idpRequest = $this->startSso('sso-redirect-loa2-2');
        $this->postIdpResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('ssp-response-joe-a-yk'),
            $idpRequest
        ));

        // No second factor verification: the response to the consume-assertion POST is the SAML response to the SP
        $this->followRedirects();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="SAMLResponse"]');
        $this->assertSelectorNotExists('input[name="gateway_verify_yubikey[yubikeyInput]"]');

        $this->assertSuccessfulSsoResponse(
            self::DEFAULT_SP_ENTITY_ID,
            self::DEFAULT_SP_ACS_URL,
            self::JOE_A_YK_NAME_ID,
            'gateway-sso-response-joe-a-yk-loa3'
        );

        // The cookie is not rewritten when the authentication was verified by the cookie
        $this->assertSsoCookieIsNotSetInCurrentResponse();
    }

    /**
     * Cross-flow and cross-SP: after an SSO authentication at default-sp wrote the cookie, an SFO request at
     * second-sp (sfo-redirect-joe-a-yk-loa2) is answered directly with the SFO response to the SP, without a second
     * factor. The cookie is not bound to an SP: it is shared across SPs and flows, tied to the user + second factor.
     */
    public function testSsoCookieIsAlsoHonoredInAnSfoRequestAtSecondSp(): void
    {
        $this->authenticateAtDefaultSpAndWriteCookie('ssp-response-joe-a-yk');

        $query = $this->samlFixtures->loadRedirectAuthnRequest('sfo-redirect-joe-a-yk-loa2');
        $this->spRequestId = SamlRequestParser::fromRedirectUrl($query)->getId();
        $this->client->request('GET', self::GATEWAY_SFO_URL . '?' . $query, server: ['HTTPS' => 'on']);

        // The SFO request is answered directly with the SFO response to the SP (no redirect to a second factor)
        $this->followRedirects();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="SAMLResponse"]');

        $this->assertSuccessfulSsoResponse(
            self::SECOND_SP_ENTITY_ID,
            self::SECOND_SP_ACS_URL,
            self::JOE_A_YK_NAME_ID,
            'gateway-sfo-response-joe-a-yk-loa3-redirect'
        );
        $this->assertSsoCookieIsNotSetInCurrentResponse();
    }

    /**
     * The use-side SP flag: after an SSO authentication at default-sp wrote the cookie, an SSO request to third-sp
     * (allow_sso_on_2fa false) still requires the second factor: the Yubikey OTP form is shown again.
     */
    public function testSsoCookieIsIgnoredWhenTheSpDoesNotAllowSsoOn2fa(): void
    {
        $this->authenticateAtDefaultSpAndWriteCookie('ssp-response-joe-a-yk');

        $this->startSsoAndAuthenticateAtIdp('sso-redirect-third-sp-loa2-1', 'ssp-response-joe-a-yk');
        $this->followRedirects();
        $this->assertYubikeyOtpFormIsShown();
    }

    /**
     * ForceAuthn override: with the cookie present, an SSO request with ForceAuthn="true" (sso-redirect-loa2-forceauthn)
     * at default-sp still requires the second factor.
     */
    public function testSsoCookieIsIgnoredWhenForceAuthnIsRequested(): void
    {
        $this->authenticateAtDefaultSpAndWriteCookie('ssp-response-joe-a-yk');

        $this->startSsoAndAuthenticateAtIdp('sso-redirect-loa2-forceauthn', 'ssp-response-joe-a-yk');
        $this->followRedirects();
        $this->assertYubikeyOtpFormIsShown();
    }

    /**
     * LoA gating, negative: joe-a-sms authenticates at default-sp with his SMS token (LoA 2), writing a cookie with
     * LoA 2. An SSO request for LoA 3 (sso-redirect-loa3-1) as joe-a-sms does not use the cookie (2 < 3): joe-a-sms
     * has no second factor that gives LoA 3, so the Gateway sends a LoA-cannot-be-given error response to the SP.
     */
    public function testSsoCookieWithLowerLoaThanRequestedIsIgnored(): void
    {
        $cookieValue = $this->authenticateAtDefaultSpAndWriteCookie('ssp-response-joe-a-sms');
        $this->assertSame(2.0, $cookieValue->getLoa(), 'The SMS token of joe-a-sms gives LoA 2');
        $this->assertSame($this->secondFactorIdOf(self::JOE_A_SMS_NAME_ID, 'sms'), $cookieValue->secondFactorId());
        $this->assertSuccessfulSsoResponse(
            self::DEFAULT_SP_ENTITY_ID,
            self::DEFAULT_SP_ACS_URL,
            self::JOE_A_SMS_NAME_ID,
            'gateway-sso-response-joe-a-sms-loa2'
        );

        $idpRequest = $this->startSso('sso-redirect-loa3-1');
        $this->postIdpResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('ssp-response-joe-a-sms'),
            $idpRequest
        ));

        // The cookie is not used: no successful response is sent, but the LoA-cannot-be-given error
        $this->followRedirects();
        $this->assertResponseIsSuccessful();
        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_RESPONDER, $samlResponse->getStatusCode());
        $this->assertNull($samlResponse->getNameId());
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequestId);
        $this->assertSamlResponseTimestampsAreValid($samlResponse);
        $this->assertSsoCookieIsNotSetInCurrentResponse();
    }

    /**
     * LoA gating, positive counter-test: the cookie of joe-a-yk (Yubikey, LoA 3) also satisfies an SSO request for
     * LoA 3 (sso-redirect-loa3-1) at default-sp: the second factor is skipped and the response to the SP is sent
     * directly, without a new Set-Cookie.
     */
    public function testSsoCookieWithSufficientLoaSkipsSecondFactorForLoa3Request(): void
    {
        $this->authenticateAtDefaultSpAndWriteCookie('ssp-response-joe-a-yk');

        $idpRequest = $this->startSso('sso-redirect-loa3-1');
        $this->postIdpResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('ssp-response-joe-a-yk'),
            $idpRequest
        ));

        $this->followRedirects();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="SAMLResponse"]');

        $this->assertSuccessfulSsoResponse(
            self::DEFAULT_SP_ENTITY_ID,
            self::DEFAULT_SP_ACS_URL,
            self::JOE_A_YK_NAME_ID,
            'gateway-sso-response-joe-a-yk-loa3-sso-cookie'
        );
        $this->assertSame(self::LOA3, $this->getSamlResponseFromCurrentPage()->getAuthnContextClassRef());
        $this->assertSsoCookieIsNotSetInCurrentResponse();
    }

    /**
     * The cookie is tied to the user: joe-a-yk's cookie, presented during the SSO login of joe-b1 at default-sp, is
     * ignored (identityId does not match the NameID of joe-b1): the Gateway does not send a successful response for
     * joe-b1.
     */
    public function testSsoCookieIssuedToAnotherUserIsIgnored(): void
    {
        $this->authenticateAtDefaultSpAndWriteCookie('ssp-response-joe-a-yk');

        $this->startSsoAndAuthenticateAtIdp('sso-redirect-loa2-2', 'ssp-response-joe-b1');

        $this->assertNoSuccessfulSamlResponseFor(self::JOE_B1_NAME_ID);
    }

    // ----- Forged cookie values (encrypted with the real helper and injected into the client cookie jar) -----

    /**
     * A cookie whose tokenId (second factor) is unknown to the Gateway (a revoked token) is ignored: the second
     * factor is required again.
     */
    public function testSsoCookieWithUnknownSecondFactorIdIsIgnored(): void
    {
        $this->setSsoCookieInClient($this->cryptoHelper()->encrypt(
            $this->forgeCookieValue(
                self::JOE_A_YK_NAME_ID,
                '00000000-0000-4000-8000-000000000000',
                3.0,
                new DateTimeImmutable('now', new DateTimeZone('UTC'))
            )
        ));

        $this->startSsoAndAuthenticateAtIdp('sso-redirect-loa2-1', 'ssp-response-joe-a-yk');
        $this->followRedirects();
        $this->assertYubikeyOtpFormIsShown();
    }

    /**
     * A cookie with an authenticationTime older than the cookie lifetime (28800 s) plus the grace period (60 s) is
     * expired and ignored; a cookie with an authenticationTime in the future is rejected as well. In both cases the
     * second factor is required again.
     */
    public function testSsoCookieWithExpiredOrFutureAuthenticationTimeIsIgnored(): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        // Expired: authenticationTime + 28800 + 60 (grace) is in the past
        $this->setSsoCookieInClient($this->cryptoHelper()->encrypt(
            $this->forgeCookieValue(self::JOE_A_YK_NAME_ID, $this->secondFactorIdOf(self::JOE_A_YK_NAME_ID, 'yubikey'), 3.0, $now->modify('-28920 seconds'))
        ));
        $this->startSsoAndAuthenticateAtIdp('sso-redirect-loa2-1', 'ssp-response-joe-a-yk');
        $this->followRedirects();
        $this->assertYubikeyOtpFormIsShown();

        // Future authenticationTime
        $this->setSsoCookieInClient($this->cryptoHelper()->encrypt(
            $this->forgeCookieValue(self::JOE_A_YK_NAME_ID, $this->secondFactorIdOf(self::JOE_A_YK_NAME_ID, 'yubikey'), 3.0, $now->modify('+1 hour'))
        ));
        $this->startSsoAndAuthenticateAtIdp('sso-redirect-loa2-2', 'ssp-response-joe-a-yk');
        $this->followRedirects();
        $this->assertYubikeyOtpFormIsShown();
    }

    /**
     * A cookie encrypted with a different key than the configured sso_encryption_key, and a cookie with tampered
     * ciphertext, can not be decrypted: both are ignored (DecryptionFailedException results in no cookie value), the
     * second factor is required again, and the Gateway does not error out (no 500).
     */
    public function testSsoCookieEncryptedWithAnotherKeyOrTamperedIsIgnored(): void
    {
        $cookieValue = $this->forgeCookieValue(
            self::JOE_A_YK_NAME_ID,
            $this->secondFactorIdOf(self::JOE_A_YK_NAME_ID, 'yubikey'),
            3.0,
            new DateTimeImmutable('now', new DateTimeZone('UTC'))
        );

        // Encrypted with another (wrong) key
        $wrongKeyHelper = new HaliteCryptoHelper(new Configuration(
            self::SSO_COOKIE_NAME,
            'persistent',
            self::SSO_COOKIE_LIFETIME,
            str_repeat('ab', 32)
        ));
        $this->setSsoCookieInClient($wrongKeyHelper->encrypt($cookieValue));
        $this->startSsoAndAuthenticateAtIdp('sso-redirect-loa2-1', 'ssp-response-joe-a-yk');
        $this->followRedirects();
        $this->assertYubikeyOtpFormIsShown();

        // Valid encryption, but tampered ciphertext
        $encrypted = $this->cryptoHelper()->encrypt($cookieValue);
        $tampered = substr($encrypted, 0, -1) . ($encrypted[strlen($encrypted) - 1] === 'A' ? 'B' : 'A');
        $this->setSsoCookieInClient($tampered);
        $this->startSsoAndAuthenticateAtIdp('sso-redirect-loa2-2', 'ssp-response-joe-a-yk');
        $this->followRedirects();
        $this->assertYubikeyOtpFormIsShown();
    }

    // ----- Flow helpers -----

    /**
     * Skip the test when the devconf captures for a user do not exist yet: his second_factor row in database.sql
     * and his IdP response fixture. Capture them from devconf (register a vetted token for the user, recapture the
     * database, capture the IdP response with SAML Tracer), see docs/FUNCTIONAL_TESTING.md.
     */
    private function skipIfUserIsNotCapturedFromDevconf(string $userSlug, string $nameId, string $idpResponseFixture): void
    {
        $fixturePath = __DIR__ . '/../Fixtures/devconf/saml/responses/' . $idpResponseFixture . '.php';
        $databaseSql = file_get_contents(__DIR__ . '/../Fixtures/devconf/database.sql');

        if (!file_exists($fixturePath) || !str_contains($databaseSql, $userSlug)) {
            $this->markTestSkipped(sprintf(
                'The devconf captures for %s do not exist yet (missing the second_factor row in database.sql and/or the fixture ssp-response fixture "%s"). '
                . 'Register a vetted token for the user in devconf, recapture database.sql and capture the IdP response, see docs/FUNCTIONAL_TESTING.md.',
                $nameId,
                $idpResponseFixture
            ));
        }
    }

    /**
     * Send the SSO AuthnRequest fixture $authnRequest, and follow the redirects until the Gateway redirects to the
     * IdP. Keeps the request ID of the SP in $spRequestId.
     *
     * @return SamlRequestParser the AuthnRequest the Gateway sent to the IdP
     */
    private function startSso(string $authnRequest): SamlRequestParser
    {
        $query = $this->samlFixtures->loadRedirectAuthnRequest($authnRequest);
        $this->spRequestId = SamlRequestParser::fromRedirectUrl($query)->getId();
        $this->client->request('GET', self::GATEWAY_SSO_URL . '?' . $query, server: ['HTTPS' => 'on']);

        return SamlRequestParser::fromRedirectUrl($this->followRedirectsUntilLocationStartsWith(self::IDP_SSO_URL));
    }

    /**
     * Send the SSO AuthnRequest fixture $authnRequest, and let the IdP authenticate the user: post the (resigned)
     * captured IdP response fixture $idpResponse to the Gateway's consume-assertion endpoint. The client has the
     * Gateway's response to that POST afterwards (a redirect to the second factor, or the response to the SP).
     */
    private function startSsoAndAuthenticateAtIdp(string $authnRequest, string $idpResponse): void
    {
        $idpRequest = $this->startSso($authnRequest);
        $this->postIdpResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse($idpResponse),
            $idpRequest
        ));
    }

    private function postIdpResponse(string $base64Response): void
    {
        $this->client->request(
            'POST',
            self::GATEWAY_ACS_URL,
            ['SAMLResponse' => $base64Response],
            server: ['HTTPS' => 'on']
        );
    }

    /**
     * Perform a full SSO authentication at default-sp as the user of the IdP response fixture $idpResponse
     * (ssp-response-joe-a-yk or ssp-response-joe-a-sms), including the second factor, up to the SAML response page.
     * Asserts the SSO on 2FA cookie is set in that response and in the client cookie jar, and returns the decrypted
     * cookie value.
     */
    private function authenticateAtDefaultSpAndWriteCookie(string $idpResponse): CookieValue
    {
        $this->startSsoAndAuthenticateAtIdp('sso-redirect-loa2-1', $idpResponse);
        $this->followRedirects();
        if ($idpResponse === 'ssp-response-joe-a-sms') {
            $this->submitSmsOtp(self::JOE_A_SMS_MSISDN);
        } else {
            $this->submitYubikeyOtp($this->secondFactorIdentifierOf(self::JOE_A_YK_NAME_ID, 'yubikey'));
        }
        $this->followRedirects();

        $this->assertSsoCookieIsInClientJar();
        $cookie = $this->assertSsoCookieIsSetInCurrentResponse();
        $this->assertSsoCookieAttributes($cookie);

        return $this->decryptCookieValue($cookie->getValue());
    }

    /**
     * Assert the current page posts a successful, signed SAML Response for $nameId to the SP ($spAcsUrl, audience
     * $spEntityId), answering the current SP AuthnRequest. When $baselineName is not null, the response is also
     * compared with that baseline.
     */
    private function assertSuccessfulSsoResponse(
        string $spEntityId,
        string $spAcsUrl,
        string $nameId,
        ?string $baselineName,
    ): void {
        $this->assertResponseIsSuccessful();

        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_SUCCESS, $samlResponse->getStatusCode());
        $this->assertSame($spEntityId, $samlResponse->getAudience());
        $this->assertSame($nameId, $samlResponse->getNameId());
        $this->assertTrue($samlResponse->isSigned(), 'SAML response should be signed');
        $this->assertSamlResponseSignatureIsValid($samlResponse);
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequestId);
        $this->assertSamlResponseTimestampsAreValid($samlResponse);
        if ($baselineName !== null) {
            $this->assertSamlResponseMatchesBaseline($samlResponse, $baselineName);
        }
        $this->assertSame($spAcsUrl, $this->getFormAction());
    }

    private function assertYubikeyOtpFormIsShown(): void
    {
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="gateway_verify_yubikey[yubikeyInput]"]');
    }

    /**
     * Assert the current response does not post a successful SAML Response for $nameId (it is a redirect to a
     * second factor verification, or an error SAML response).
     */
    private function assertNoSuccessfulSamlResponseFor(string $nameId): void
    {
        $crawler = $this->client->getCrawler();
        if ($crawler->filter('input[name="SAMLResponse"], input[name="_SAMLResponse"]')->count() === 0) {
            $this->addToAssertionCount(1);

            return;
        }

        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertFalse(
            $samlResponse->getStatusCode() === Constants::STATUS_SUCCESS && $samlResponse->getNameId() === $nameId,
            sprintf('The SSO on 2FA cookie must not skip the second factor: the Gateway sent a successful SAML Response for "%s"', $nameId)
        );
    }

    // ----- Second factor lookup -----

    /**
     * Look up the second factor of type $tokenType ('yubikey', 'sms', ...) of the user with NameID $nameId in the
     * second_factor table of the devconf fixture database (loaded into the functional SQLite database in setUp).
     * The NameID and token type are stable, but the second_factor_id (a GUID) and the second_factor_identifier
     * (renumbered at capture time) change when database.sql is recaptured from a recreated devconf database, so
     * they are looked up here instead of being hard-coded. The lookup must match exactly one row.
     *
     * @return array{second_factor_id: string, second_factor_identifier: string}
     */
    private function secondFactorOf(string $nameId, string $tokenType): array
    {
        $rows = static::getContainer()->get('doctrine.dbal.gateway_connection')->fetchAllAssociative(
            'SELECT second_factor_id, second_factor_identifier FROM second_factor WHERE name_id = ? AND second_factor_type = ?',
            [$nameId, $tokenType]
        );
        $this->assertCount(
            1,
            $rows,
            sprintf('Expected exactly one "%s" second factor for %s in the devconf fixture database', $tokenType, $nameId)
        );

        return $rows[0];
    }

    /** The second_factor_id of the $tokenType second factor of the user with NameID $nameId (the tokenId in the SSO cookie) */
    private function secondFactorIdOf(string $nameId, string $tokenType): string
    {
        return $this->secondFactorOf($nameId, $tokenType)['second_factor_id'];
    }

    /** The second_factor_identifier of the $tokenType second factor of the user with NameID $nameId (e.g. the public ID of a Yubikey) */
    private function secondFactorIdentifierOf(string $nameId, string $tokenType): string
    {
        return $this->secondFactorOf($nameId, $tokenType)['second_factor_identifier'];
    }

    // ----- SSO cookie helpers -----

    private function getSsoCookieFromCurrentResponse(): ?HttpCookie
    {
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === self::SSO_COOKIE_NAME) {
                return $cookie;
            }
        }

        return null;
    }

    private function assertSsoCookieIsSetInCurrentResponse(): HttpCookie
    {
        $cookie = $this->getSsoCookieFromCurrentResponse();
        $this->assertNotNull($cookie, 'The response should set the SSO on 2FA cookie');
        $this->assertNotEmpty($cookie->getValue(), 'The SSO on 2FA cookie value should not be empty');

        return $cookie;
    }

    private function assertSsoCookieIsNotSetInCurrentResponse(): void
    {
        $this->assertNull(
            $this->getSsoCookieFromCurrentResponse(),
            'The response should not set the SSO on 2FA cookie'
        );
    }

    private function assertSsoCookieAttributes(HttpCookie $cookie): void
    {
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isSecure(), 'The SSO on 2FA cookie should be Secure');
        $this->assertTrue($cookie->isHttpOnly(), 'The SSO on 2FA cookie should be HttpOnly');
        $this->assertSame(HttpCookie::SAMESITE_NONE, $cookie->getSameSite());
        $expires = $cookie->getExpiresTime();
        $this->assertGreaterThanOrEqual(
            time() + self::SSO_COOKIE_LIFETIME - 60,
            $expires,
            'The SSO on 2FA cookie should expire after the configured lifetime'
        );
        $this->assertLessThanOrEqual(time() + self::SSO_COOKIE_LIFETIME + 60, $expires);
    }

    private function assertSsoCookieIsInClientJar(): void
    {
        $found = false;
        foreach ($this->client->getCookieJar()->all() as $cookie) {
            if ($cookie->getName() === self::SSO_COOKIE_NAME) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'The client cookie jar should contain the SSO on 2FA cookie');
    }

    /**
     * The real Halite crypto helper of the functional environment (gateway.service.sso_2fa_cookie_crypto_helper),
     * used to decrypt the Set-Cookie header and to encrypt forged cookie values.
     */
    private function cryptoHelper(): HaliteCryptoHelper
    {
        $helper = static::getContainer()->get('gateway.service.sso_2fa_cookie_crypto_helper');
        $this->assertInstanceOf(HaliteCryptoHelper::class, $helper);

        return $helper;
    }

    private function decryptCookieValue(string $encryptedCookieValue): CookieValue
    {
        return $this->cryptoHelper()->decrypt($encryptedCookieValue);
    }

    /**
     * Build a CookieValue with an explicit authentication time (CookieValue::from() always uses "now"), e.g. to forge
     * an expired or future cookie.
     */
    private function forgeCookieValue(
        string $identityId,
        string $secondFactorId,
        float $loa,
        DateTimeImmutable $authenticationTime,
    ): CookieValue {
        return CookieValue::deserialize(json_encode([
            'tokenId' => $secondFactorId,
            'identityId' => $identityId,
            'loa' => $loa,
            'authenticationTime' => $authenticationTime->format(DATE_RFC3339_EXTENDED),
        ]));
    }

    /**
     * Put an (encrypted) SSO on 2FA cookie value in the client cookie jar, as if a previous response of the Gateway
     * had set it.
     */
    private function setSsoCookieInClient(string $encryptedCookieValue): void
    {
        $this->client->getCookieJar()->set(new BrowserKitCookie(
            self::SSO_COOKIE_NAME,
            $encryptedCookieValue,
            time() + self::SSO_COOKIE_LIFETIME,
            '/',
            'gateway.dev.openconext.local',
            true,
            true,
            false,
            HttpCookie::SAMESITE_NONE,
        ));
    }

    private function assertFreshAuthenticationTime(int $authenticationTime): void
    {
        $this->assertLessThanOrEqual(time(), $authenticationTime, 'The authentication time should not be in the future');
        $this->assertGreaterThanOrEqual(time() - 300, $authenticationTime, 'The authentication time should be fresh');
    }
}
