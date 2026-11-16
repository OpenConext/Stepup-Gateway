<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Gateway;

use SAML2\Constants;
use Surfnet\StepupGateway\Tests\Functional\Support\GatewayWebTestCase;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlRequestParser;

/**
 * Functional tests for the Second Factor Only (SFO) authentication flow.
 *
 * Test data comes from tests/Functional/Fixtures/devconf/database.sql (captured from devconf):
 * - SP: second-sp (SFO-enabled) from devssp
 * - User: joe-a-yk from institution-a.example.com (has a vetted Yubikey token)
 * - Institution: institution-a.example.com (whitelisted, SFO-allowed)
 */
class SfoFlowTest extends GatewayWebTestCase
{
    /**
     * SFO endpoint on the devconf gateway hostname (the fixture's signature and Destination are bound to it).
     */
    private const GATEWAY_SFO_URL = 'https://gateway.dev.openconext.local/second-factor-only/single-sign-on';

    /**
     * SFO-enabled SP from devssp (second-sp).
     * Entity ID matches the devconf middleware configuration.
     */
    private const SP_ENTITY_ID = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/second-sp';

    /**
     * ACS URL for the second-sp (debugsp module).
     */
    private const SP_ACS_URL = 'https://ssp.dev.openconext.local/simplesaml/module.php/debugsp/acs/second-sp';

    /**
     * Institution that joe-a-yk belongs to.
     * Whitelisted in devconf and allowed by SFO NameID patterns.
     */
    private const INSTITUTION = 'institution-a.example.com';

    /**
     * NameID of the test user with a Yubikey token.
     * The -yk suffix indicates this user is meant for Yubikey testing.
     */
    private const NAME_ID = 'urn:collab:person:institution-a.example.com:joe-a-yk';

    /**
     * SFO LoA 2 (Level of Assurance 2 for Second Factor Only).
     */
    private const SFO_LOA_2 = 'http://dev.openconext.local/assurance/sfo-level2';

    /**
     * Test a successful SFO authentication with Yubikey via HTTP-Redirect binding.
     *
     * Flow:
     * 1. Send signed AuthnRequest via HTTP-Redirect to /second-factor-only/single-sign-on
     * 2. Gateway validates the request and redirects to Yubikey verification
     * 3. Submit an OTP of the Yubikey of joe-a-yk (the Yubikey test double does not call Yubico)
     * 4. Gateway returns a signed SAML Response via auto-submit form
     */
    private string $spRequestId = '';

    public function testSuccessfulSfoWithYubikeyViaHttpRedirect(): void
    {
        // Step 1: Send signed AuthnRequest via HTTP-Redirect binding
        // The fixture contains the full query string: SAMLRequest=...&SigAlg=...&Signature=...
        $authnRequestQuery = $this->samlFixtures->loadRedirectAuthnRequest('sfo-redirect-joe-a-yk-loa2');
        $this->spRequestId = SamlRequestParser::fromRedirectUrl($authnRequestQuery)->getId();

        // The AuthnRequest signature and Destination are bound to the devconf hostname
        $this->client->request(
            'GET',
            self::GATEWAY_SFO_URL . '?' . $authnRequestQuery,
            server: ['HTTPS' => 'on']
        );

        // Step 2: Should redirect to Yubikey verification
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertYubikeyOtpSubmissionYieldsSuccessfulResponse('gateway-sfo-response-joe-a-yk-loa3-redirect');
    }

    /**
     * Test a successful SFO authentication with Yubikey via HTTP-POST binding.
     *
     * Same flow as the HTTP-Redirect test, but the signed AuthnRequest is POSTed as a form
     * parameter (base64-encoded XML with an enveloped XML signature).
     */
    public function testSuccessfulSfoWithYubikeyViaHttpPost(): void
    {
        $samlRequest = $this->samlFixtures->loadPostAuthnRequest('sfo-post-joe-a-yk-loa2');
        $this->spRequestId = SamlRequestParser::fromPostBinding($samlRequest)->getId();

        $this->client->request(
            'POST',
            self::GATEWAY_SFO_URL,
            ['SAMLRequest' => $samlRequest],
            server: ['HTTPS' => 'on']
        );

        // Should redirect to Yubikey verification
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertYubikeyOtpSubmissionYieldsSuccessfulResponse('gateway-sfo-response-joe-a-yk-loa3-post');
    }

    /**
     * Test a successful SFO authentication with SMS (joe-a-sms) via HTTP-Redirect binding. The SMS test double keeps
     * the SMS the Gateway sends, the test reads the OTP from it.
     */
    public function testSuccessfulSfoWithSmsViaHttpRedirect(): void
    {
        $authnRequestQuery = $this->samlFixtures->loadRedirectAuthnRequest('sfo-redirect-joe-a-sms-loa2');
        $this->spRequestId = SamlRequestParser::fromRedirectUrl($authnRequestQuery)->getId();
        $this->client->request('GET', self::GATEWAY_SFO_URL . '?' . $authnRequestQuery, server: ['HTTPS' => 'on']);

        // The Gateway asks to send an SMS to joe-a-sms
        $this->followRedirects();
        $this->submitSmsOtp(self::JOE_A_SMS_MSISDN);

        $this->assertSuccessfulResponse(
            'urn:collab:person:institution-a.example.com:joe-a-sms',
            'gateway-sfo-response-joe-a-sms-loa2-redirect'
        );
    }

    /**
     * Shared by the binding tests: expects the Yubikey OTP form, submits it and asserts the SAML response.
     */
    private function assertYubikeyOtpSubmissionYieldsSuccessfulResponse(string $baselineName): void
    {
        // Step 1 and 2: Should show Yubikey OTP form, submit an OTP of the Yubikey of joe-a-yk
        $this->submitYubikeyOtp(self::JOE_A_YK_YUBIKEY_ID);

        $this->assertSuccessfulResponse(self::NAME_ID, $baselineName);
    }

    /**
     * Assert the current page posts a successful SAML Response for $nameId to the SP.
     */
    private function assertSuccessfulResponse(string $nameId, string $baselineName): void
    {
        // Step 3: Should render SAML response (auto-submit form)
        $this->assertResponseIsSuccessful();

        // Step 4: Parse and assert SAML response
        $samlResponse = $this->getSamlResponseFromCurrentPage();

        $this->assertSame(
            Constants::STATUS_SUCCESS,
            $samlResponse->getStatusCode(),
            'Expected successful SAML response'
        );

        $this->assertSame(
            self::SP_ENTITY_ID,
            $samlResponse->getAudience(),
            'Audience should match SP entity ID'
        );

        $this->assertSame(
            $nameId,
            $samlResponse->getNameId(),
            'NameID should match the authenticated user'
        );

        $this->assertTrue(
            $samlResponse->isSigned(),
            'SAML response should be signed'
        );
        $this->assertSamlResponseSignatureIsValid($samlResponse);
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequestId);
        $this->assertSamlResponseTimestampsAreValid($samlResponse);
        $this->assertSamlResponseMatchesBaseline($samlResponse, $baselineName);

        // Step 5: Verify form action points to SP ACS
        $formAction = $this->getFormAction();
        $this->assertSame(
            self::SP_ACS_URL,
            $formAction,
            'Form should POST to SP ACS URL'
        );
    }
}
