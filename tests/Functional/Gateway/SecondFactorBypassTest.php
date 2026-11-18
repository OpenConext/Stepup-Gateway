<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Gateway;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use SAML2\Constants;
use Surfnet\StepupGateway\Tests\Functional\Support\GatewayWebTestCase;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlRequestParser;
use Surfnet\StepupGateway\Tests\Functional\Support\TestDouble\YubikeyService;

/**
 * Test that various ways in which an attacker can manipulate the authentication process do not succeed
 *
 * Premise: There is an attacker that is in possession of a victim's first factor credential and in possession of a
 *          valid second factor that is linked to another account that the attacker controls (i.e. not linked to the
 *          victim's account).
 *
 * An attacker can start an authentication as the victim in a browser (or other tool) that the attacker controls, and
 * so can control the messages that are sent to the Stepup-Gateway.
 *
 * The attacker must not be able to authenticate at a LoA > 1 as the victim. (controlling the user's first factor means
 * authentication at LoA 1 is possible)
 *
 * testSecondFactorOfAttackerCanNotCompleteAuthenticationOfVictim() runs a "switcheroo" attack for each combination of
 * users and authentication flows in its data providers, it tests that a valid second factor of the attacker can not
 * complete the authentication of the victim:
 *
 * 1. Start an authentication as the attacker, and prepare the attacker's second factor, but do not use it yet:
 *    - Demo GSSP (jane-a1, joe-b1): capture the GSSP Response, do not post it;
 *    - Yubikey (joe-a-yk): keep the Yubikey OTP form, do not submit it;
 *    - SMS (joe-a-sms): keep the form to enter the SMS OTP and the OTP that was sent, do not submit it.
 * 2. In the same session, start an authentication as the victim, and stop at the victim's second factor.
 * 3. Use the second factor of the attacker of step 1: post the GSSP Response, or submit the Yubikey or SMS OTP form.
 * 4. The Gateway must not send a successful SAML Response for the victim.
 *
 * The data providers are grouped by the second factor of the attacker:
 * - gsspSameFlowAttacks(): the GSSP Response of the attacker
 * - gsspCrossFlowAttacks(): the same, across SFO and SSO.
 * - gsspProxyAttacks(): the GSSP Response of the attacker (joe-b1) for a registration through the GSSP proxy
 *   (Self-Service), used for an SSO or SFO authentication.
 * - yubikeyAttacks(): the Yubikey OTP of the attacker.
 * - smsAttacks(): the SMS OTP of the attacker.
 *
 * testYubikeyOfOtherUserCanNotCompleteAuthenticationOfVictim() tests the OTP of another Yubikey at the Yubikey of the
 * victim. testSmsOfOtherUserCanNotCompleteAuthenticationOfVictim() tests the same for SMS: the OTP that was sent to
 * the SMS token of another user (jane-a-sms) at the SMS OTP form of the victim (joe-a-sms).
 *
 * The Yubikey and SMS tests depend on the test doubles of the functional tests (Support\TestDouble): the Yubikey double
 * accepts any well-formed OTP, but keeps the check of the Gateway that the OTP is of the Yubikey of the user; the SMS
 * double keeps the SMS so the test can read the OTP.
 *
 * A "flow" is one of the SFO entry points (HTTP-Redirect, HTTP-POST and the ADFS MFA extension), SSO, or (for the
 * attacker only) a Self-Service registration through the GSSP proxy.
 *
 * In the SSO flow the user is not in the AuthnRequest, but is authenticated by the remote IdP, so starting an
 * authentication includes the first factor authentication at the IdP.
 *
 * In the SFO flow the identity of the user is part of the AuthnRequest, and at reception of a valid AuthnRequest it
 * is assumed that the first factor of the user was successfully authenticated.
 */
class SecondFactorBypassTest extends GatewayWebTestCase
{
    private const GATEWAY_SFO_URL = 'https://gateway.dev.openconext.local/second-factor-only/single-sign-on';
    private const GATEWAY_SSO_URL = 'https://gateway.dev.openconext.local/authentication/single-sign-on';
    private const GATEWAY_SSO_ACS_URL = 'https://gateway.dev.openconext.local/authentication/consume-assertion';
    private const IDP_SSO_URL = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/idp/singleSignOnService';
    private const GATEWAY_GSSP_SSO_URL = 'https://gateway.dev.openconext.local/gssp/demo_gssp/single-sign-on';
    private const GATEWAY_GSSP_ACS_URL = 'https://gateway.dev.openconext.local/gssp/demo_gssp/consume-assertion';
    private const DEMO_GSSP_SSO_URL = 'https://demogssp.dev.openconext.local/saml/sso';

    /** The users, each with one second factor (see SECOND_FACTORS) */
    private const JANE_A1 = 'jane-a1';
    private const JANE_A_SMS = 'jane-a-sms';
    private const JOE_A_YK = 'joe-a-yk';
    private const JOE_A_SMS = 'joe-a-sms';
    private const JOE_B1 = 'joe-b1';

    /** The second factor types */
    private const GSSP = 'demo_gssp';
    private const YUBIKEY = 'yubikey';
    private const SMS = 'sms';

    /** The second factor of each user (database.sql) */
    private const SECOND_FACTORS = [
        self::JANE_A1 => self::GSSP,
        self::JANE_A_SMS => self::SMS,
        self::JOE_A_YK => self::YUBIKEY,
        self::JOE_A_SMS => self::SMS,
        self::JOE_B1 => self::GSSP,
    ];

    /** The flows: the SFO entry points (HTTP-Redirect, HTTP-POST and the ADFS MFA extension) and SSO (HTTP-Redirect) */
    private const SFO_REDIRECT = 'sfo-redirect';
    private const SFO_POST = 'sfo-post';
    private const SFO_ADFS = 'sfo-adfs';
    private const SSO = 'sso';
    private const SELFSERVICE_REGISTRATION = 'selfservice-registration';

    /** The AuthMethod the ADFS MFA extension sends, always ADFS.SCSA */
    private const ADFS_AUTH_METHOD = 'ADFS.SCSA';

    /** The Context the ADFS MFA extension sends, opaque for the Gateway */
    private const ADFS_CONTEXT = 'context-of-the-adfs-mfa-extension';

    /**
     * @param string $attacker        the attacker, one of the users (JANE_A1, JOE_A_YK, JOE_A_SMS, JOE_B1)
     * @param string $attackerFlow    the flow the attacker uses, one of the SFO_* constants, SSO or
     *                                SELFSERVICE_REGISTRATION
     * @param string $attackerRequest the AuthnRequest fixture the attacker sends
     * @param string $victim          the victim, another user
     * @param string $victimFlow      the flow the victim uses
     * @param string $victimRequest   the AuthnRequest fixture the victim sends
     */
    #[DataProvider('gsspSameFlowAttacks')]
    #[DataProvider('gsspCrossFlowAttacks')]
    #[DataProvider('yubikeyAttacks')]
    #[DataProvider('smsAttacks')]
    #[DataProvider('gsspProxyAttacks')]
    public function testSecondFactorOfAttackerCanNotCompleteAuthenticationOfVictim(
        string $attacker,
        string $attackerFlow,
        string $attackerRequest,
        string $victim,
        string $victimFlow,
        string $victimRequest,
    ): void {
        // Step 1: the attacker starts an authentication, and prepares their second factor
        $this->startAuthentication($attackerFlow, $attackerRequest, $attacker);
        $useSecondFactorOfAttacker = $this->prepareSecondFactor($attacker);

        // Step 2: in the same session, an authentication of the victim is started, up to the victim's second factor
        $this->startAuthentication($victimFlow, $victimRequest, $victim);
        $this->continueToSecondFactor($victim);

        // Step 3: the attacker uses their second factor of step 1, instead of the second factor of the victim
        $useSecondFactorOfAttacker();
        $this->followRedirects();

        // Step 4: the Gateway must not authenticate the victim
        $this->assertNoSuccessfulSamlResponseFor(self::nameIdOf($victim));
    }

    /**
     * Data provider: the GSSP Response of the attacker (jane-a1), the attacker and the victim use the same flow.
     *
     * Each row is [attacker, attacker flow, attacker AuthnRequest fixture, victim, victim flow, victim AuthnRequest
     * fixture]: the first line of a row is the attacker, the second line the victim. The fixtures are in
     * Fixtures/devconf/saml/authn-requests, all for LoA 2.
     *
     * SSO is tested twice:
     * - "other request": the attacker and the victim use a different AuthnRequest (with a different ID);
     * - "same request": the attacker and the victim use the same AuthnRequest (with the same ID). The Gateway does not
     *   reject an AuthnRequest ID it has seen before.
     * An SFO AuthnRequest contains the NameID of the user, so they're necessarily different requests, and their IDs
     * would be different.
     *
     * @return array<string, array{string, string, string, string, string, string}>
     */
    public static function gsspSameFlowAttacks(): array
    {
        return [
            'attacker jane-a1 sfo-redirect, victim joe-a-yk sfo-redirect' => [
                self::JANE_A1, self::SFO_REDIRECT, 'sfo-redirect-jane-a1-loa2',
                self::JOE_A_YK, self::SFO_REDIRECT, 'sfo-redirect-joe-a-yk-loa2',
            ],
            'attacker jane-a1 sfo-post, victim joe-a-yk sfo-post' => [
                self::JANE_A1, self::SFO_POST, 'sfo-post-jane-a1-loa2',
                self::JOE_A_YK, self::SFO_POST, 'sfo-post-joe-a-yk-loa2',
            ],
            'attacker jane-a1 sfo-adfs, victim joe-a-yk sfo-adfs' => [
                self::JANE_A1, self::SFO_ADFS, 'sfo-adfs-jane-a1-loa2',
                self::JOE_A_YK, self::SFO_ADFS, 'sfo-adfs-joe-a-yk-loa2',
            ],
            'attacker jane-a1 sso, victim joe-a-yk sso, other request' => [
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-2',
            ],
            'attacker jane-a1 sso, victim joe-a-yk sso, same request' => [
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-1',
            ],
            'attacker jane-a1 sfo-redirect, victim joe-a-sms sfo-redirect' => [
                self::JANE_A1, self::SFO_REDIRECT, 'sfo-redirect-jane-a1-loa2',
                self::JOE_A_SMS, self::SFO_REDIRECT, 'sfo-redirect-joe-a-sms-loa2',
            ],
            'attacker jane-a1 sfo-post, victim joe-a-sms sfo-post' => [
                self::JANE_A1, self::SFO_POST, 'sfo-post-jane-a1-loa2',
                self::JOE_A_SMS, self::SFO_POST, 'sfo-post-joe-a-sms-loa2',
            ],
            'attacker jane-a1 sfo-adfs, victim joe-a-sms sfo-adfs' => [
                self::JANE_A1, self::SFO_ADFS, 'sfo-adfs-jane-a1-loa2',
                self::JOE_A_SMS, self::SFO_ADFS, 'sfo-adfs-joe-a-sms-loa2',
            ],
            'attacker jane-a1 sso, victim joe-a-sms sso, other request' => [
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_SMS, self::SSO, 'sso-redirect-loa2-2',
            ],
            'attacker jane-a1 sso, victim joe-a-sms sso, same request' => [
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_SMS, self::SSO, 'sso-redirect-loa2-1',
            ],
        ];
    }

    /**
     * Data provider: the GSSP Response of the attacker (jane-a1), the attacker and the victim (joe-a-yk or joe-a-sms)
     * use a different flow, one an SFO entry point and the other SSO.
     *
     * Each row is [attacker, attacker flow, attacker AuthnRequest fixture, victim, victim flow, victim AuthnRequest
     * fixture], like in gsspSameFlowAttacks(). For victim joe-a-yk each SFO entry point is combined with SSO, with the
     * attacker in either flow. For victim joe-a-sms only sfo-post to SSO, and SSO to sfo-post and sfo-adfs.
     *
     * @return array<string, array{string, string, string, string, string, string}>
     */
    public static function gsspCrossFlowAttacks(): array
    {
        return [
            'attacker jane-a1 sfo-redirect, victim joe-a-yk sso' => [
                self::JANE_A1, self::SFO_REDIRECT, 'sfo-redirect-jane-a1-loa2',
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-2',
            ],
            'attacker jane-a1 sfo-post, victim joe-a-yk sso' => [
                self::JANE_A1, self::SFO_POST, 'sfo-post-jane-a1-loa2',
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-2',
            ],
            'attacker jane-a1 sfo-adfs, victim joe-a-yk sso' => [
                self::JANE_A1, self::SFO_ADFS, 'sfo-adfs-jane-a1-loa2',
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-2',
            ],
            'attacker jane-a1 sso, victim joe-a-yk sfo-redirect' => [
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_YK, self::SFO_REDIRECT, 'sfo-redirect-joe-a-yk-loa2',
            ],
            'attacker jane-a1 sso, victim joe-a-yk sfo-post' => [
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_YK, self::SFO_POST, 'sfo-post-joe-a-yk-loa2',
            ],
            'attacker jane-a1 sso, victim joe-a-yk sfo-adfs' => [
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_YK, self::SFO_ADFS, 'sfo-adfs-joe-a-yk-loa2',
            ],
            'attacker jane-a1 sfo-post, victim joe-a-sms sso' => [
                self::JANE_A1, self::SFO_POST, 'sfo-post-jane-a1-loa2',
                self::JOE_A_SMS, self::SSO, 'sso-redirect-loa2-2',
            ],
            'attacker jane-a1 sso, victim joe-a-sms sfo-post' => [
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_SMS, self::SFO_POST, 'sfo-post-joe-a-sms-loa2',
            ],
            'attacker jane-a1 sso, victim joe-a-sms sfo-adfs' => [
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_SMS, self::SFO_ADFS, 'sfo-adfs-joe-a-sms-loa2',
            ],
        ];
    }

    /**
     * Data provider: the Yubikey OTP of the attacker (joe-a-yk), the attacker and the victim use the same flow.
     *
     * Each row is [attacker, attacker flow, attacker AuthnRequest fixture, victim, victim flow, victim AuthnRequest
     * fixture], like in gsspSameFlowAttacks(). The victim is jane-a1 (Demo GSSP) or joe-a-sms (SMS).
     *
     * @return array<string, array{string, string, string, string, string, string}>
     */
    public static function yubikeyAttacks(): array
    {
        return [
            'attacker joe-a-yk sfo-redirect, victim jane-a1 sfo-redirect' => [
                self::JOE_A_YK, self::SFO_REDIRECT, 'sfo-redirect-joe-a-yk-loa2',
                self::JANE_A1, self::SFO_REDIRECT, 'sfo-redirect-jane-a1-loa2',
            ],
            'attacker joe-a-yk sfo-post, victim jane-a1 sfo-post' => [
                self::JOE_A_YK, self::SFO_POST, 'sfo-post-joe-a-yk-loa2',
                self::JANE_A1, self::SFO_POST, 'sfo-post-jane-a1-loa2',
            ],
            'attacker joe-a-yk sfo-adfs, victim jane-a1 sfo-adfs' => [
                self::JOE_A_YK, self::SFO_ADFS, 'sfo-adfs-joe-a-yk-loa2',
                self::JANE_A1, self::SFO_ADFS, 'sfo-adfs-jane-a1-loa2',
            ],
            'attacker joe-a-yk sso, victim jane-a1 sso, other request' => [
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-1',
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-2',
            ],
            'attacker joe-a-yk sso, victim jane-a1 sso, same request' => [
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-1',
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
            ],
            'attacker joe-a-yk sfo-redirect, victim joe-a-sms sfo-redirect' => [
                self::JOE_A_YK, self::SFO_REDIRECT, 'sfo-redirect-joe-a-yk-loa2',
                self::JOE_A_SMS, self::SFO_REDIRECT, 'sfo-redirect-joe-a-sms-loa2',
            ],
            'attacker joe-a-yk sfo-post, victim joe-a-sms sfo-post' => [
                self::JOE_A_YK, self::SFO_POST, 'sfo-post-joe-a-yk-loa2',
                self::JOE_A_SMS, self::SFO_POST, 'sfo-post-joe-a-sms-loa2',
            ],
            'attacker joe-a-yk sfo-adfs, victim joe-a-sms sfo-adfs' => [
                self::JOE_A_YK, self::SFO_ADFS, 'sfo-adfs-joe-a-yk-loa2',
                self::JOE_A_SMS, self::SFO_ADFS, 'sfo-adfs-joe-a-sms-loa2',
            ],
            'attacker joe-a-yk sso, victim joe-a-sms sso, other request' => [
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_SMS, self::SSO, 'sso-redirect-loa2-2',
            ],
            'attacker joe-a-yk sso, victim joe-a-sms sso, same request' => [
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_SMS, self::SSO, 'sso-redirect-loa2-1',
            ],
        ];
    }

    /**
     * Data provider: the SMS OTP of the attacker (joe-a-sms), the attacker and the victim use the same flow.
     *
     * Each row is [attacker, attacker flow, attacker AuthnRequest fixture, victim, victim flow, victim AuthnRequest
     * fixture], like in gsspSameFlowAttacks(). The victim is joe-a-yk (Yubikey) or jane-a1 (Demo GSSP).
     *
     * @return array<string, array{string, string, string, string, string, string}>
     */
    public static function smsAttacks(): array
    {
        return [
            'attacker joe-a-sms sfo-redirect, victim joe-a-yk sfo-redirect' => [
                self::JOE_A_SMS, self::SFO_REDIRECT, 'sfo-redirect-joe-a-sms-loa2',
                self::JOE_A_YK, self::SFO_REDIRECT, 'sfo-redirect-joe-a-yk-loa2',
            ],
            'attacker joe-a-sms sfo-post, victim joe-a-yk sfo-post' => [
                self::JOE_A_SMS, self::SFO_POST, 'sfo-post-joe-a-sms-loa2',
                self::JOE_A_YK, self::SFO_POST, 'sfo-post-joe-a-yk-loa2',
            ],
            'attacker joe-a-sms sfo-adfs, victim joe-a-yk sfo-adfs' => [
                self::JOE_A_SMS, self::SFO_ADFS, 'sfo-adfs-joe-a-sms-loa2',
                self::JOE_A_YK, self::SFO_ADFS, 'sfo-adfs-joe-a-yk-loa2',
            ],
            'attacker joe-a-sms sso, victim joe-a-yk sso, other request' => [
                self::JOE_A_SMS, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-2',
            ],
            'attacker joe-a-sms sso, victim joe-a-yk sso, same request' => [
                self::JOE_A_SMS, self::SSO, 'sso-redirect-loa2-1',
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-1',
            ],
            'attacker joe-a-sms sfo-redirect, victim jane-a1 sfo-redirect' => [
                self::JOE_A_SMS, self::SFO_REDIRECT, 'sfo-redirect-joe-a-sms-loa2',
                self::JANE_A1, self::SFO_REDIRECT, 'sfo-redirect-jane-a1-loa2',
            ],
            'attacker joe-a-sms sfo-post, victim jane-a1 sfo-post' => [
                self::JOE_A_SMS, self::SFO_POST, 'sfo-post-joe-a-sms-loa2',
                self::JANE_A1, self::SFO_POST, 'sfo-post-jane-a1-loa2',
            ],
            'attacker joe-a-sms sfo-adfs, victim jane-a1 sfo-adfs' => [
                self::JOE_A_SMS, self::SFO_ADFS, 'sfo-adfs-joe-a-sms-loa2',
                self::JANE_A1, self::SFO_ADFS, 'sfo-adfs-jane-a1-loa2',
            ],
            'attacker joe-a-sms sso, victim jane-a1 sso, other request' => [
                self::JOE_A_SMS, self::SSO, 'sso-redirect-loa2-1',
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-2',
            ],
            'attacker joe-a-sms sso, victim jane-a1 sso, same request' => [
                self::JOE_A_SMS, self::SSO, 'sso-redirect-loa2-1',
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
            ],
        ];
    }

    /**
     * Data provider: the GSSP Response of the attacker (joe-b1) for a registration through the GSSP proxy.
     *
     * Each row is [attacker, attacker flow, attacker AuthnRequest fixture, victim, victim flow, victim AuthnRequest
     * fixture], like in gsspSameFlowAttacks(). The attacker starts a registration of a Demo GSSP token in Self-Service
     * (the AuthnRequest of Self-Service to the GSSP proxy of the Gateway), and keeps the GSSP Response for their own
     * token. The victim then authenticates with SFO or SSO.
     *
     * @return array<string, array{string, string, string, string, string, string}>
     */
    public static function gsspProxyAttacks(): array
    {
        return [
            'attacker joe-b1 selfservice-registration, victim joe-a-yk sfo-redirect' => [
                self::JOE_B1, self::SELFSERVICE_REGISTRATION, 'selfservice-register-demo-gssp',
                self::JOE_A_YK, self::SFO_REDIRECT, 'sfo-redirect-joe-a-yk-loa2',
            ],
            'attacker joe-b1 selfservice-registration, victim joe-a-yk sso' => [
                self::JOE_B1, self::SELFSERVICE_REGISTRATION, 'selfservice-register-demo-gssp',
                self::JOE_A_YK, self::SSO, 'sso-redirect-loa2-1',
            ],
            'attacker joe-b1 selfservice-registration, victim joe-a-sms sfo-redirect' => [
                self::JOE_B1, self::SELFSERVICE_REGISTRATION, 'selfservice-register-demo-gssp',
                self::JOE_A_SMS, self::SFO_REDIRECT, 'sfo-redirect-joe-a-sms-loa2',
            ],
            'attacker joe-b1 selfservice-registration, victim joe-a-sms sfo-post' => [
                self::JOE_B1, self::SELFSERVICE_REGISTRATION, 'selfservice-register-demo-gssp',
                self::JOE_A_SMS, self::SFO_POST, 'sfo-post-joe-a-sms-loa2',
            ],
            'attacker joe-b1 selfservice-registration, victim joe-a-sms sfo-adfs' => [
                self::JOE_B1, self::SELFSERVICE_REGISTRATION, 'selfservice-register-demo-gssp',
                self::JOE_A_SMS, self::SFO_ADFS, 'sfo-adfs-joe-a-sms-loa2',
            ],
            'attacker joe-b1 selfservice-registration, victim joe-a-sms sso' => [
                self::JOE_B1, self::SELFSERVICE_REGISTRATION, 'selfservice-register-demo-gssp',
                self::JOE_A_SMS, self::SSO, 'sso-redirect-loa2-1',
            ],
            'attacker joe-b1 selfservice-registration, victim jane-a1 sfo-redirect' => [
                self::JOE_B1, self::SELFSERVICE_REGISTRATION, 'selfservice-register-demo-gssp',
                self::JANE_A1, self::SFO_REDIRECT, 'sfo-redirect-jane-a1-loa2',
            ],
            'attacker joe-b1 selfservice-registration, victim jane-a1 sso' => [
                self::JOE_B1, self::SELFSERVICE_REGISTRATION, 'selfservice-register-demo-gssp',
                self::JANE_A1, self::SSO, 'sso-redirect-loa2-1',
            ],
        ];
    }

    /**
     * The Yubikey of another user is used for the authentication of the victim (joe-a-yk): at the Yubikey OTP form of
     * the victim, the attacker submits an OTP of another Yubikey that is registered in Stepup (the Yubikey of admin).
     *
     * The Gateway checks that the public ID in the OTP is the one it expects. To show that the rejection is caused by
     * that check, the OTP of the Yubikey of the victim is submitted after trying the attacker's, and must succeed.
     *
     * @param string $flow         the flow the victim uses, one of the SFO_* constants or SSO
     * @param string $authnRequest the AuthnRequest fixture the victim sends
     */
    #[DataProvider('victimYubikeyFlows')]
    public function testYubikeyOfOtherUserCanNotCompleteAuthenticationOfVictim(string $flow, string $authnRequest): void
    {
        $this->startAuthentication($flow, $authnRequest, self::JOE_A_YK);
        $this->followRedirects();

        // The OTP of another Yubikey is rejected: the Gateway shows the Yubikey OTP form again, with an error
        $this->submitYubikeyOtp(self::ADMIN_YUBIKEY_ID);
        $this->followRedirects();
        $this->assertNoSuccessfulSamlResponseFor(self::nameIdOf(self::JOE_A_YK));
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('form[name="gateway_verify_yubikey"] .form-error');

        // The OTP of the Yubikey of the victim is accepted
        $form = $this->client->getCrawler()->selectButton('gateway_verify_yubikey_submit')->form();
        $form['gateway_verify_yubikey[yubikeyInput]'] = YubikeyService::otpForPublicId(self::JOE_A_YK_YUBIKEY_ID);
        $this->client->submit($form);
        $this->followRedirects();
        $this->assertSame(Constants::STATUS_SUCCESS, $this->getSamlResponseFromCurrentPage()->getStatusCode());
        $this->assertSame(self::nameIdOf(self::JOE_A_YK), $this->getSamlResponseFromCurrentPage()->getNameId());
    }

    /**
     * Data provider: each flow, with an AuthnRequest that leads to the Yubikey of joe-a-yk.
     *
     * Each row is [flow, AuthnRequest fixture].
     *
     * @return array<string, array{string, string}>
     */
    public static function victimYubikeyFlows(): array
    {
        return [
            'sfo-redirect' => [self::SFO_REDIRECT, 'sfo-redirect-joe-a-yk-loa2'],
            'sfo-post' => [self::SFO_POST, 'sfo-post-joe-a-yk-loa2'],
            'sfo-adfs' => [self::SFO_ADFS, 'sfo-adfs-joe-a-yk-loa2'],
            'sso' => [self::SSO, 'sso-redirect-loa2-1'],
        ];
    }

    /**
     * The SMS OTP of another user is used for the authentication of the victim (joe-a-sms): at the SMS OTP form of
     * the victim, the attacker submits the OTP that was sent to the SMS token of another user (jane-a-sms).
     *
     * To have that OTP, an authentication of jane-a-sms is started first in the same session: the Gateway sends an
     * SMS to the phone of jane-a-sms and stores the OTP for the second factor of jane-a-sms. The SMS endpoint of
     * the Gateway verifies the submitted OTP against the second factor of the victim only, so that earlier
     * authentication does not change anything for the victim. To show that the rejection is caused by that check,
     * the OTP sent to the phone of the victim is submitted next, and must succeed.
     *
     * @param string $flow         the flow the victim uses, one of the SFO_* constants or SSO
     * @param string $authnRequest the AuthnRequest fixture the victim sends
     */
    #[DataProvider('victimSmsFlows')]
    public function testSmsOfOtherUserCanNotCompleteAuthenticationOfVictim(string $flow, string $authnRequest): void
    {
        // Start an authentication of the other user (jane-a-sms), up to the SMS OTP form: the Gateway sends the SMS
        // to the phone of jane-a-sms
        $this->startAuthentication(self::SSO, 'sso-redirect-loa2-1', self::JANE_A_SMS);
        $this->continueToSecondFactor(self::JANE_A_SMS);
        $otpOfOtherUser = $this->getSmsOtpSentTo(self::JANE_A_SMS_MSISDN);

        // Start an authentication of the victim (joe-a-sms), up to the SMS OTP form
        $this->startAuthentication($flow, $authnRequest, self::JOE_A_SMS);
        $this->continueToSecondFactor(self::JOE_A_SMS);

        // The OTP sent to the phone of the other user is rejected: the Gateway shows the SMS OTP form again, with
        // an error
        $this->submitSmsChallenge($otpOfOtherUser);
        $this->followRedirects();
        $this->assertNoSuccessfulSamlResponseFor(self::nameIdOf(self::JOE_A_SMS));
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('form[name="gateway_verify_sms_challenge"] .form-error');

        // The OTP sent to the phone of the victim is accepted
        $this->submitSmsChallenge($this->getSmsOtpSentTo(self::JOE_A_SMS_MSISDN));
        $this->followRedirects();
        $this->assertSame(Constants::STATUS_SUCCESS, $this->getSamlResponseFromCurrentPage()->getStatusCode());
        $this->assertSame(self::nameIdOf(self::JOE_A_SMS), $this->getSamlResponseFromCurrentPage()->getNameId());
    }

    /**
     * Data provider: each flow, with an AuthnRequest that leads to the SMS token of joe-a-sms.
     *
     * Each row is [flow, AuthnRequest fixture].
     *
     * @return array<string, array{string, string}>
     */
    public static function victimSmsFlows(): array
    {
        return [
            'sfo-redirect' => [self::SFO_REDIRECT, 'sfo-redirect-joe-a-sms-loa2'],
            'sfo-post' => [self::SFO_POST, 'sfo-post-joe-a-sms-loa2'],
            'sfo-adfs' => [self::SFO_ADFS, 'sfo-adfs-joe-a-sms-loa2'],
            'sso' => [self::SSO, 'sso-redirect-loa2-1'],
        ];
    }

    /**
     * Step 1 of the attack: the attacker starts an authentication with their own ($user) account for which they possess
     * a valid second factor. Continue the authentication just until the point that the response (PoP) is due:
     * - SMS: The OTP code that was sent to the attacker's phone
     * - Yubikey: The OTP code of the attacker's Yubikey
     * - GSSP: The GSSP Response of the attacker's authentication (to the Demo GSSP)
     *
     * @return Closure that uses the second factor: posts the GSSP Response, or submits the Yubikey or SMS OTP form
     */
    private function prepareSecondFactor(string $user): Closure
    {
        switch (self::SECOND_FACTORS[$user]) {
            case self::GSSP:
                // The user authenticates at the Demo GSSP, and keeps the response
                $gsspRequest = SamlRequestParser::fromRedirectUrl(
                    $this->followRedirectsUntilLocationStartsWith(self::DEMO_GSSP_SSO_URL)
                );
                $gsspResponse = $this->samlResigner->resign(
                    $this->samlFixtures->loadPostResponse('demo-gssp-response-' . $user),
                    $gsspRequest
                );

                return fn () => $this->client->request(
                    'POST',
                    self::GATEWAY_GSSP_ACS_URL,
                    ['SAMLResponse' => $gsspResponse],
                    server: ['HTTPS' => 'on']
                );

            case self::YUBIKEY:
                // The user keeps the Yubikey OTP form, and enters an OTP of their Yubikey
                $this->followRedirects();
                $this->assertYubikeyOtpFormIsShown();
                $form = $this->client->getCrawler()->selectButton('gateway_verify_yubikey_submit')->form();
                $form['gateway_verify_yubikey[yubikeyInput]'] = YubikeyService::otpForPublicId(
                    self::JOE_A_YK_YUBIKEY_ID
                );

                return fn () => $this->client->submit($form);

            case self::SMS:
                // The Gateway sends the SMS, the user keeps the form to enter the OTP, and enters the OTP of the SMS
                $this->followRedirects();
                $this->assertSmsOtpFormIsShown();
                $form = $this->client->getCrawler()->filter('form[name="gateway_verify_sms_challenge"]')->form();
                $form['gateway_verify_sms_challenge[challenge]'] = $this->getSmsOtpSentTo(self::msisdnOf($user));

                return fn () => $this->client->submit($form);
        }

        $this->fail(sprintf('Unknown second factor of user "%s"', $user));
    }

    /**
     * Step 2 of the attack: follow the Gateway until it asks the victim ($user) for their second factor. For the Demo
     * GSSP, the redirect to the Demo GSSP is not followed. For SMS, the Gateway sends the SMS OTP to the victim.
     */
    private function continueToSecondFactor(string $user): void
    {
        switch (self::SECOND_FACTORS[$user]) {
            case self::GSSP:
                $this->followRedirectsUntilLocationStartsWith(self::DEMO_GSSP_SSO_URL);
                break;
            case self::YUBIKEY:
                $this->followRedirects();
                $this->assertYubikeyOtpFormIsShown();
                break;
            case self::SMS:
                $this->followRedirects();
                $this->assertSmsOtpFormIsShown();
                break;
        }
    }

    /**
     * Start an authentication of $user: send the AuthnRequest fixture $authnRequest using $flow. For SSO, the IdP
     * authenticates $user as well (the first factor). Afterwards the client has the response of the Gateway that leads
     * to the second factor authentication.
     */
    private function startAuthentication(string $flow, string $authnRequest, string $user): void
    {
        match ($flow) {
            self::SFO_REDIRECT => $this->client->request(
                'GET',
                self::GATEWAY_SFO_URL . '?' . $this->samlFixtures->loadRedirectAuthnRequest($authnRequest),
                server: ['HTTPS' => 'on']
            ),
            self::SFO_POST => $this->client->request(
                'POST',
                self::GATEWAY_SFO_URL,
                ['SAMLRequest' => $this->samlFixtures->loadPostAuthnRequest($authnRequest)],
                server: ['HTTPS' => 'on']
            ),
            self::SFO_ADFS => $this->client->request(
                'POST',
                self::GATEWAY_SFO_URL,
                [
                    'SAMLRequest' => $this->samlFixtures->loadPostAuthnRequest($authnRequest),
                    'AuthMethod' => self::ADFS_AUTH_METHOD,
                    'Context' => self::ADFS_CONTEXT,
                ],
                server: ['HTTPS' => 'on']
            ),
            self::SSO => $this->startSsoAuthentication($authnRequest, $user),
            self::SELFSERVICE_REGISTRATION => $this->client->request(
                'GET',
                self::GATEWAY_GSSP_SSO_URL . '?' . $this->samlFixtures->loadRedirectAuthnRequest($authnRequest),
                server: ['HTTPS' => 'on']
            ),
        };
    }

    /**
     * Send the SSO AuthnRequest fixture $authnRequest, and let the IdP authenticate $user (the first factor).
     */
    private function startSsoAuthentication(string $authnRequest, string $user): void
    {
        $this->client->request(
            'GET',
            self::GATEWAY_SSO_URL . '?' . $this->samlFixtures->loadRedirectAuthnRequest($authnRequest),
            server: ['HTTPS' => 'on']
        );
        $idpRequest = SamlRequestParser::fromRedirectUrl($this->followRedirectsUntilLocationStartsWith(self::IDP_SSO_URL));

        $this->client->request(
            'POST',
            self::GATEWAY_SSO_ACS_URL,
            ['SAMLResponse' => $this->samlResigner->resign(
                $this->samlFixtures->loadPostResponse('ssp-response-' . $user),
                $idpRequest
            )],
            server: ['HTTPS' => 'on']
        );
    }

    private static function nameIdOf(string $user): string
    {
        $institution = $user === self::JOE_B1 ? 'institution-b.example.com' : 'institution-a.example.com';

        return sprintf('urn:collab:person:%s:%s', $institution, $user);
    }

    /** The phone number (MSISDN) of the SMS token of $user, see GatewayWebTestCase */
    private static function msisdnOf(string $user): string
    {
        return match ($user) {
            self::JANE_A_SMS => self::JANE_A_SMS_MSISDN,
            self::JOE_A_SMS => self::JOE_A_SMS_MSISDN,
        };
    }

    private function assertYubikeyOtpFormIsShown(): void
    {
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="gateway_verify_yubikey[yubikeyInput]"]');
    }

    /**
     * Assert that the current page does not post a successful SAML Response for $nameId to the SP.
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
            sprintf('Second factor bypass: the Gateway sent a successful SAML Response for "%s"', $nameId)
        );
    }
}
