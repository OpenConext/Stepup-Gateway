<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Gateway;

use SAML2\Constants;
use Surfnet\StepupGateway\Tests\Functional\Support\GatewayWebTestCase;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlRequestParser;

/**
 * Functional tests for second factor only (SFO) authentication using a GSSP (the Demo GSSP).
 *
 * Test data comes from tests/Functional/Fixtures/devconf/database.sql (captured from devconf):
 * - SP: second-sp (SFO-enabled)
 * - User: jane-a1 from institution-a.example.com (has a vetted Demo GSSP token)
 *
 * The Demo GSSP response is captured from devconf. It can't be replayed as is, because it answers
 * the (random) request ID of the gateway AuthnRequest at the time and has expired. The response is
 * made valid for the AuthnRequest sent by the gateway by the SamlResponseResigner.
 */
class GsspFlowTest extends GatewayWebTestCase
{
    private const GATEWAY_SFO_URL = 'https://gateway.dev.openconext.local/second-factor-only/single-sign-on';
    private const GATEWAY_GSSP_ACS_URL = 'https://gateway.dev.openconext.local/gssp/demo_gssp/consume-assertion';
    private const DEMO_GSSP_SSO_URL = 'https://demogssp.dev.openconext.local/saml/sso';

    private const SP_ENTITY_ID = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/second-sp';
    private const SP_ACS_URL = 'https://ssp.dev.openconext.local/simplesaml/module.php/debugsp/acs/second-sp';
    private const NAME_ID = 'urn:collab:person:institution-a.example.com:jane-a1';

    public function testSuccessfulSfoWithDemoGssp(): void
    {
        $gatewayRequest = $this->startSfoAuthenticationUntilGsspRedirect();

        // The Demo GSSP answers the AuthnRequest of the gateway
        $gsspResponse = $this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('demo-gssp-response-jane-a1'),
            $gatewayRequest
        );

        $this->postGsspResponse($gsspResponse);

        // The gateway verifies the GSSP response and continues to the SFO response
        $this->followRedirects();
        $this->assertResponseIsSuccessful();

        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_SUCCESS, $samlResponse->getStatusCode());
        $this->assertSame(self::SP_ENTITY_ID, $samlResponse->getAudience());
        $this->assertSame(self::NAME_ID, $samlResponse->getNameId());
        $this->assertTrue($samlResponse->isSigned(), 'SAML response should be signed');
        $this->assertSame(self::SP_ACS_URL, $this->getFormAction());
    }

    public function testCapturedDemoGsspResponseCanNotBeReplayed(): void
    {
        $this->startSfoAuthenticationUntilGsspRedirect();

        // Unmodified response: InResponseTo does not match and the response has expired
        $this->postGsspResponse($this->samlFixtures->loadPostResponse('demo-gssp-response-jane-a1'));

        // The gateway does not authenticate the user, and sends an error response to the SP
        $this->followRedirects();
        $this->assertResponseIsSuccessful();

        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_RESPONDER, $samlResponse->getStatusCode());
        $this->assertNull($samlResponse->getNameId());
    }

    public function testDemoGsspResponseToOtherRequestIsRejected(): void
    {
        $this->startSfoAuthenticationUntilGsspRedirect();

        // Valid signature and times, but the response answers another AuthnRequest than the one of the gateway
        $otherRequest = new SamlRequestParser(
            '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="_some-other-request-id"/>'
        );
        $this->postGsspResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('demo-gssp-response-jane-a1'),
            $otherRequest
        ));

        // Unlike an expired assertion, this is not answered with an error response to the SP
        // (UnknownInResponseToException), but results in an error page
        $this->assertResponseStatusCodeSame(500);
        $this->assertSelectorNotExists('input[name="SAMLResponse"]');
    }

    /**
     * Send the SFO AuthnRequest, and follow the gateway until it redirects to the Demo GSSP.
     *
     * @return SamlRequestParser the AuthnRequest the gateway sent to the Demo GSSP
     */
    private function startSfoAuthenticationUntilGsspRedirect(): SamlRequestParser
    {
        $query = $this->samlFixtures->loadRedirectAuthnRequest('sfo-redirect-jane-a1-loa2');
        $this->client->request('GET', self::GATEWAY_SFO_URL . '?' . $query, server: ['HTTPS' => 'on']);

        for ($i = 0; $i < 5; $i++) {
            $this->assertResponseRedirects();
            $location = $this->client->getResponse()->headers->get('Location');
            if (str_starts_with($location, self::DEMO_GSSP_SSO_URL)) {
                return SamlRequestParser::fromRedirectUrl($location);
            }
            $this->client->followRedirect();
        }

        $this->fail('The gateway did not redirect to the Demo GSSP');
    }

    private function postGsspResponse(string $base64Response): void
    {
        $this->client->request(
            'POST',
            self::GATEWAY_GSSP_ACS_URL,
            ['SAMLResponse' => $base64Response],
            server: ['HTTPS' => 'on']
        );
    }

    private function followRedirects(int $max = 5): void
    {
        for ($i = 0; $i < $max && $this->client->getResponse()->isRedirect(); $i++) {
            $this->client->followRedirect();
        }
    }
}
