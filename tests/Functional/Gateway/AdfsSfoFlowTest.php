<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Gateway;

use SAML2\Constants;
use Surfnet\StepupGateway\Tests\Functional\Support\GatewayWebTestCase;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlRequestParser;

/**
 * Functional tests for the second factor only (SFO) flow of the ADFS MFA extension.
 *
 * The ADFS MFA extension uses a non-standard variation of the HTTP-POST binding:
 * - The AuthnRequest is POSTed together with two extra parameters: AuthMethod and Context
 * - The gateway POSTs the Response back with the parameter _SAMLResponse (instead of SAMLResponse), together with the
 *   AuthMethod and Context it received
 * - The Response is POSTed to the AssertionConsumerServiceURL of the AuthnRequest exactly, including its query string
 *
 * Test data comes from tests/Functional/Fixtures/devconf/database.sql (captured from devconf):
 * - SP: second-sp (SFO-enabled), the AuthnRequests were sent by devssp mimicking the ADFS MFA extension
 * - Users: joe-a-yk (Yubikey) and jane-a1 (Demo GSSP) from institution-a.example.com
 */
class AdfsSfoFlowTest extends GatewayWebTestCase
{
    private const GATEWAY_SFO_URL = 'https://gateway.dev.openconext.local/second-factor-only/single-sign-on';
    private const GATEWAY_GSSP_ACS_URL = 'https://gateway.dev.openconext.local/gssp/demo_gssp/consume-assertion';
    private const DEMO_GSSP_SSO_URL = 'https://demogssp.dev.openconext.local/saml/sso';

    private const SP_ENTITY_ID = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/second-sp';

    /**
     * The raw (not XML or HTML decoded) AssertionConsumerServiceURL of the ADFS AuthnRequests. ADFS checks the raw value
     * of the URL it receives the response on, so the gateway must use exactly this value, query string and escaping
     * included, as the action of the form. Any difference results in a general error in ADFS.
     */
    private const ADFS_ACS_URL_RAW = 'https://ssp.dev.openconext.local/simplesaml/module.php/debugsp/acs/second-sp'
        . '?SAMLRequest=request_that_must_be_kept&amp;Context=context_value_that_must_be_kept';

    /**
     * The AuthMethod ADFS sends, always ADFS.SCSA.
     */
    private const ADFS_AUTH_METHOD = 'ADFS.SCSA';

    /**
     * A Context as sent by ADFS. It is opaque for the gateway, which must return it unchanged.
     */
    private const ADFS_CONTEXT = 'NFAmxU3D7kAXYID7tf_KpnLTyJOlX89lSWz1-uFDSwqQDAHPOpXbeE3Y3MAvnSQd51X3nejkBGijzz7oo3g-vlF3J_'
        . 'DsQITSuxRguRRtlRIhfVos8UmoO4277tAvjaS6aRrgJQ-6VkN6HLHg4v9Yl6jJTD-xV9Ah46GG7XemDDU1vLC1zXnPx1_RObTIwle8Vj45LN0A_'
        . 'fX7QustIeOfOc4wOrWMDwBQ1bL0U_iCiU6HIxINpzjpXC5OpCXqvp_DIYP62iptYQtVc8PlGjp05jW3V-P4XOIbF4NuD3XQhbBXapNvt4TA6c'
        . 'JBhzZZcyulfFerLmYBYvUopqGsTQYaqddLoOLJKi4sJjNWl80hYigMcvZrBg254fT-NPm5teAswLLkW6VWNUXkk2oYRSNSEKgHueus4Afr1KOX'
        . 'TJj_zCpRGegw67C8TF0lWGJxi7i_rxHkPdTRytf6fp6PBZEJwhrQXlwwf3VwA8cp3MOZquALGCDxWaBoOfWDuxfVV7CJWT17fWJ6A1DTsxNP96'
        . 'vW1F4eQknUsIANtP7LoIu82lLnEifQBnNZXHf_Xg4a10sjk43AKAvM7yfb4s8MPSM8_w6nNryoucwykHW8o9xfdtdaO51yG6mdE5XGl3DPff8C'
        . 'Tx6Qpr7xGnv83x7rtEXV73Q8xzeYV67HMctz727HzqiZKMQqFNRojcCFfLQaOYMK_Ymr6otWqXL2VpAhGi9XIJ8at2nJWXuZ-VIU8e3fIB2NAV'
        . 'z5A_-PlA33JxHCL-NVKV0ByQAc5cG-hfy_4uWkRTHDWSZ0HYsd0TFzGS0h4nhLE4sWucnvqH1tnMxlyvtrlYwSBNwuzFLQ_kAKjzLC0_2LdCkz'
        . '8DE8cvY_INxaGsqci3aH67NB7gd9vyeKCMtThetNfwltgsStW-cqqDXR8deelDn6UnCOrlzxNgpY3txPi4PTViOA_dQcdTEc-rOJaxIsv-u_1f'
        . 'u.ezke0NwbMmS35Dmbt0L_8TIYwofEMDJ68jFzfVVhopjIxm5Ide4RznpTD_jqQ_1VbDLa_Ze9HW_BnG17y7hmrh3iSdIMNFK8Cc19RWctBbPnV'
        . 'eojTt9K1U91sBNSs3WeKQUVI66EpmSmJDHsJjzr4fvD_lumKQCfE38xrEpheDAFxZec3E5P1vV5YK3iJWwLdR78fnIVm_p4AIrNM4B4VGVOjSqa'
        . 'apqHP_-fFMPjNs-OQzt7Y0hh8i-1bjc6JkoV10u3lV9QSdfI7FOJopUTrOXH1aVPdORr6vxKORH1MTaUAxOYfzBqYx1d8god6wa7iIfTy3-7C4'
        . 'X5YG7qnvbBth';

    private SamlRequestParser $spRequest;

    /** The raw value of the AssertionConsumerServiceURL attribute of the AuthnRequest of the SP */
    private string $spRequestAcsUrlRaw;

    public function testSuccessfulAdfsSfoWithYubikey(): void
    {
        $this->postAdfsAuthnRequest('sfo-adfs-joe-a-yk-loa2');

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->submitYubikeyOtp();

        $this->assertSuccessfulAdfsResponse(
            'urn:collab:person:institution-a.example.com:joe-a-yk',
            'gateway-sfo-response-joe-a-yk-loa3-adfs'
        );
    }

    public function testSuccessfulAdfsSfoWithDemoGssp(): void
    {
        $this->postAdfsAuthnRequest('sfo-adfs-jane-a1-loa2');

        $gatewayRequest = SamlRequestParser::fromRedirectUrl(
            $this->followRedirectsUntilLocationStartsWith(self::DEMO_GSSP_SSO_URL)
        );

        // The Demo GSSP answers the AuthnRequest of the gateway
        $this->client->request(
            'POST',
            self::GATEWAY_GSSP_ACS_URL,
            ['SAMLResponse' => $this->samlResigner->resign(
                $this->samlFixtures->loadPostResponse('demo-gssp-response-jane-a1'),
                $gatewayRequest
            )],
            server: ['HTTPS' => 'on']
        );
        $this->followRedirects();

        $this->assertSuccessfulAdfsResponse(
            'urn:collab:person:institution-a.example.com:jane-a1',
            'gateway-sfo-response-jane-a1-loa3-adfs'
        );
    }

    /**
     * POST the AuthnRequest like the ADFS MFA extension does: HTTP-POST binding plus the AuthMethod and Context.
     */
    private function postAdfsAuthnRequest(string $fixtureName): void
    {
        $samlRequest = $this->samlFixtures->loadPostAuthnRequest($fixtureName);
        $this->spRequest = SamlRequestParser::fromPostBinding($samlRequest);
        $this->spRequestAcsUrlRaw = $this->getRawAttribute(
            base64_decode($samlRequest, true),
            'samlp:AuthnRequest',
            'AssertionConsumerServiceURL'
        );

        $this->assertSame(
            self::ADFS_ACS_URL_RAW,
            $this->spRequestAcsUrlRaw,
            'The fixture should request the ADFS ACS URL'
        );

        $this->client->request(
            'POST',
            self::GATEWAY_SFO_URL,
            [
                'SAMLRequest' => $samlRequest,
                'AuthMethod' => self::ADFS_AUTH_METHOD,
                'Context' => self::ADFS_CONTEXT,
            ],
            server: ['HTTPS' => 'on']
        );
    }

    /**
     * Assert the current page is the ADFS variant of the HTTP-POST binding, with a successful SAML Response.
     */
    private function assertSuccessfulAdfsResponse(string $nameId, string $baselineName): void
    {
        $this->assertResponseIsSuccessful();

        $form = $this->client->getCrawler()->filter('form');
        $this->assertCount(1, $form, 'Expected exactly one form');
        $this->assertSame('post', strtolower((string) $form->attr('method')), 'The form should use POST');

        // Bit for bit the raw ACS URL of the AuthnRequest, including the query string. Compare the raw HTML: the
        // crawler (like the browser) would decode the attribute value.
        $this->assertSame(
            $this->spRequestAcsUrlRaw,
            $this->getRawAttribute($this->client->getResponse()->getContent(), 'form', 'action'),
            'The form should POST to the raw AssertionConsumerServiceURL of the AuthnRequest'
        );

        // The ADFS parameters, and nothing else: no SAMLResponse (or RelayState)
        $values = $form->form()->getValues();
        ksort($values);
        $this->assertSame(['AuthMethod', 'Context', '_SAMLResponse'], array_keys($values), 'ADFS POST parameters');
        $this->assertSame(self::ADFS_AUTH_METHOD, $values['AuthMethod'], 'AuthMethod should be returned');
        $this->assertSame(self::ADFS_CONTEXT, $values['Context'], 'Context should be returned unchanged');

        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame($values['_SAMLResponse'], $samlResponse->getBase64Response());

        $this->assertSame(Constants::STATUS_SUCCESS, $samlResponse->getStatusCode());
        $this->assertSame(self::SP_ENTITY_ID, $samlResponse->getAudience());
        $this->assertSame($nameId, $samlResponse->getNameId());
        $this->assertTrue($samlResponse->isSigned(), 'SAML response should be signed');
        $this->assertSamlResponseSignatureIsValid($samlResponse);
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequest->getId());
        $this->assertSamlResponseTimestampsAreValid($samlResponse);
        $this->assertSamlResponseMatchesBaseline($samlResponse, $baselineName);
    }

    /**
     * Get the raw value of an attribute of the first $element start tag in $markup, as it is in the markup: without
     * decoding entities (e.g. &amp;).
     */
    private function getRawAttribute(string $markup, string $element, string $attribute): string
    {
        $pattern = sprintf('/<%s\s[^>]*?\b%s="([^"]*)"/', preg_quote($element, '/'), preg_quote($attribute, '/'));
        $this->assertSame(
            1,
            preg_match($pattern, $markup, $matches),
            sprintf('No %s attribute found on <%s>', $attribute, $element)
        );

        return $matches[1];
    }
}
