<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Gateway;

use SAML2\Constants;
use Surfnet\StepupGateway\Tests\Functional\Support\GatewayWebTestCase;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlRequestParser;

/**
 * Functional tests for the "normal" (SSO) authentication flow, requiring LoA 2.
 *
 * Test data comes from tests/Functional/Fixtures/devconf/database.sql (captured from devconf):
 * - SP: default-sp (not SFO)
 * - Users: joe-a-yk (vetted Yubikey) and jane-a1 (vetted Demo GSSP token), institution-a.example.com
 *
 * The first factor is the SimpleSAMLphp IdP of devconf. Its response is captured from devconf, and
 * made valid for the AuthnRequest sent by the gateway by the SamlResponseResigner.
 */
class SsoFlowTest extends GatewayWebTestCase
{
    private const GATEWAY_SSO_URL = 'https://gateway.dev.openconext.local/authentication/single-sign-on';
    private const GATEWAY_ACS_URL = 'https://gateway.dev.openconext.local/authentication/consume-assertion';
    private const GATEWAY_GSSP_ACS_URL = 'https://gateway.dev.openconext.local/gssp/demo_gssp/consume-assertion';
    private const IDP_SSO_URL = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/idp/singleSignOnService';
    private const DEMO_GSSP_SSO_URL = 'https://demogssp.dev.openconext.local/saml/sso';

    private const SP_ENTITY_ID = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/default-sp';
    private const SP_ACS_URL = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp';

    private string $spRequestId = '';

    public function testSuccessfulSsoWithYubikey(): void
    {
        $this->authenticateAtIdp('ssp-response-joe-a-yk');

        // The gateway asks for the second factor: the Yubikey of joe-a-yk
        $this->followRedirects();
        $this->submitYubikeyOtp();
        $this->followRedirects();

        $this->assertSuccessfulResponseForSp(
            'urn:collab:person:institution-a.example.com:joe-a-yk',
            'gateway-sso-response-joe-a-yk-loa3'
        );
    }

    public function testSuccessfulSsoWithDemoGssp(): void
    {
        $this->authenticateAtIdp('ssp-response-jane-a1');

        // The gateway sends jane-a1 to the Demo GSSP
        $gsspRequest = SamlRequestParser::fromRedirectUrl(
            $this->followRedirectsUntilLocationStartsWith(self::DEMO_GSSP_SSO_URL)
        );

        $this->client->request(
            'POST',
            self::GATEWAY_GSSP_ACS_URL,
            ['SAMLResponse' => $this->samlResigner->resign(
                $this->samlFixtures->loadPostResponse('demo-gssp-response-jane-a1'),
                $gsspRequest
            )],
            server: ['HTTPS' => 'on']
        );
        $this->followRedirects();

        $this->assertSuccessfulResponseForSp(
            'urn:collab:person:institution-a.example.com:jane-a1',
            'gateway-sso-response-jane-a1-loa3'
        );
    }

    public function testCapturedIdpResponseCanNotBeReplayed(): void
    {
        $this->startSsoUntilIdpRedirect();

        // Unmodified response: InResponseTo does not match and the response has expired
        $this->postIdpResponse($this->samlFixtures->loadPostResponse('ssp-response-joe-a-yk'));

        $this->assertErrorResponseForSp('gateway-sso-response-error-replayed-idp-response');
    }

    public function testIdpResponseToOtherRequestIsRejected(): void
    {
        $this->startSsoUntilIdpRedirect();

        // Valid signature and times, but the response answers another AuthnRequest than the one of the gateway
        $otherRequest = new SamlRequestParser(
            '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="_some-other-request-id"/>'
        );
        $this->postIdpResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('ssp-response-joe-a-yk'),
            $otherRequest
        ));

        $this->followRedirects();
        $this->assertResponseStatusCodeSame(500);
        $this->assertSelectorNotExists('input[name="SAMLResponse"]');
    }

    /**
     * Send the SSO AuthnRequest and let the (resigned) SimpleSAMLphp IdP response authenticate the user.
     * The client has the gateway response of the consume assertion request afterwards.
     */
    private function authenticateAtIdp(string $idpResponseFixture): void
    {
        $idpRequest = $this->startSsoUntilIdpRedirect();

        $this->postIdpResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse($idpResponseFixture),
            $idpRequest
        ));
    }

    /**
     * @return SamlRequestParser the AuthnRequest the gateway sent to the IdP
     */
    private function startSsoUntilIdpRedirect(): SamlRequestParser
    {
        $query = $this->samlFixtures->loadRedirectAuthnRequest('sso-redirect-loa2');
        $this->spRequestId = SamlRequestParser::fromRedirectUrl($query)->getId();
        $this->client->request('GET', self::GATEWAY_SSO_URL . '?' . $query, server: ['HTTPS' => 'on']);

        return SamlRequestParser::fromRedirectUrl($this->followRedirectsUntilLocationStartsWith(self::IDP_SSO_URL));
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

    private function assertSuccessfulResponseForSp(string $nameId, string $baselineName): void
    {
        $this->assertResponseIsSuccessful();

        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_SUCCESS, $samlResponse->getStatusCode());
        $this->assertSame(self::SP_ENTITY_ID, $samlResponse->getAudience());
        $this->assertSame($nameId, $samlResponse->getNameId());
        $this->assertTrue($samlResponse->isSigned(), 'SAML response should be signed');
        $this->assertSamlResponseSignatureIsValid($samlResponse);
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequestId);
        $this->assertSamlResponseTimestampsAreValid($samlResponse);
        $this->assertSamlResponseMatchesBaseline($samlResponse, $baselineName);
        $this->assertSame(self::SP_ACS_URL, $this->getFormAction());
    }

    /**
     * The gateway does not authenticate the user, and sends an error response to the SP.
     */
    private function assertErrorResponseForSp(string $baselineName): void
    {
        $this->followRedirects();
        $this->assertResponseIsSuccessful();

        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_RESPONDER, $samlResponse->getStatusCode());
        $this->assertNull($samlResponse->getNameId());
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequestId);
        $this->assertSamlResponseTimestampsAreValid($samlResponse);
        $this->assertSamlResponseMatchesBaseline($samlResponse, $baselineName);
    }
}
