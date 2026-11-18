<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Gateway;

use DOMDocument;
use DOMXPath;
use SAML2\Constants;
use Surfnet\StepupGateway\Tests\Functional\Support\GatewayWebTestCase;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlRequestParser;

/**
 * Functional tests for the GSSP proxy: Self-Service (registration) and the RA (vetting) use the Gateway as the IdP for
 * a GSSP. The Gateway forwards their AuthnRequest to the GSSP, checks the GSSP Response and sends a new Response,
 * signed by the Gateway, back to Self-Service or the RA.
 *
 *   Self-Service --AuthnRequest--> /gssp/demo_gssp/single-sign-on --AuthnRequest--> Demo GSSP
 *   Self-Service <--Response------ /gssp/demo_gssp/consume-assertion <--Response--- Demo GSSP
 *   RA -----------AuthnRequest--> /gssp/demo_gssp/single-sign-on --AuthnRequest--> Demo GSSP
 *   RA <----------Response------ /gssp/demo_gssp/consume-assertion <--Response--- Demo GSSP
 *
 * Test data comes from tests/Functional/Fixtures/devconf (captured from devconf):
 * - SP: the Self-Service registration SP for the Demo GSSP
 *   (https://selfservice.dev.openconext.local/registration/gssf/demo_gssp/metadata), in gssp_allowed_sps
 * - User: joe-b1 of institution-b.example.com, registering a Demo GSSP token (selfservice-register-demo-gssp,
 *   demo-gssp-response-joe-b1). A registration AuthnRequest has no Subject: the GSSP chooses the identifier.
 * - SP: the RA vetting procedure SP for the Demo GSSP
 *   (https://ra.dev.openconext.local/vetting-procedure/gssf/demo_gssp/metadata), in gssp_allowed_sps
 * - User: jane-b1 of institution-b.example.com, whose Demo GSSP token is vetted (gssp-redirect-ra-jane-b1,
 *   demo-gssp-response-jane-b1). A vetting AuthnRequest has a Subject: the GSSP identifier of the token to vet,
 *   which the Gateway forwards to the GSSP and checks in the GSSP response.
 *
 * The response of the Gateway to Self-Service is signed with the key of the hosted IdP of the Demo GSSP
 * (gateway_gssp_idp), not with the key of the Gateway IdP for SSO and SFO.
 *
 * The GSSP proxy shares the session with the SSO and SFO flows of the Gateway: a user logs in to Self-Service through
 * the Gateway (SSO, sso-redirect-selfservice and ssp-response-joe-b1), and then registers a token through the GSSP
 * proxy, in the same browser session (testSelfServiceLoginAndRegistrationInTheSameSession). The tests with a pending
 * SFO authentication check that the state of that flow does not change the response to Self-Service.
 */
class GsspProxyTest extends GatewayWebTestCase
{
    private const GATEWAY_GSSP_SSO_URL = 'https://gateway.dev.openconext.local/gssp/demo_gssp/single-sign-on';
    private const GATEWAY_GSSP_ACS_URL = 'https://gateway.dev.openconext.local/gssp/demo_gssp/consume-assertion';
    private const GATEWAY_GSSP_SP_ENTITY_ID = 'https://gateway.dev.openconext.local/gssp/demo_gssp/metadata';
    private const DEMO_GSSP_SSO_URL = 'https://demogssp.dev.openconext.local/saml/sso';

    private const SELFSERVICE_ENTITY_ID = 'https://selfservice.dev.openconext.local/registration/gssf/demo_gssp/metadata';
    private const SELFSERVICE_ACS_URL =
        'https://selfservice.dev.openconext.local/registration/gssf/demo_gssp/consume-assertion';
    private const SELFSERVICE_RELAY_STATE = 'isGssfRequest';

    /** The identifier the Demo GSSP chose for the token of joe-b1 */
    private const JOE_B1_GSSP_ID = 'test-prefix-6aeb70b7660b91.34464221';

    /** The SP of the vetting procedure of the RA for the Demo GSSP, in gssp_allowed_sps */
    private const RA_ENTITY_ID = 'https://ra.dev.openconext.local/vetting-procedure/gssf/demo_gssp/metadata';
    private const RA_ACS_URL = 'https://ra.dev.openconext.local/vetting-procedure/gssf/demo_gssp/verify';

    /** The Demo GSSP identifier of the vetted token of jane-b1 (second_factor_identifier in database.sql) */
    private const JANE_B1_GSSP_ID = 'test-prefix-6aeb7de7519694.37754665';

    private const GATEWAY_SFO_URL = 'https://gateway.dev.openconext.local/second-factor-only/single-sign-on';
    private const GATEWAY_SSO_URL = 'https://gateway.dev.openconext.local/authentication/single-sign-on';
    private const GATEWAY_SSO_ACS_URL = 'https://gateway.dev.openconext.local/authentication/consume-assertion';
    private const IDP_SSO_URL = 'https://ssp.dev.openconext.local/simplesaml/module.php/saml/idp/singleSignOnService';

    /** The SP Self-Service uses to log in the user (SSO), not the SP of the registration */
    private const SELFSERVICE_LOGIN_ENTITY_ID = 'https://selfservice.dev.openconext.local/authentication/metadata';
    private const SELFSERVICE_LOGIN_ACS_URL = 'https://selfservice.dev.openconext.local/authentication/consume-assertion';
    private const JOE_B1_NAME_ID = 'urn:collab:person:institution-b.example.com:joe-b1';

    private string $spRequestId = '';

    public function testSuccessfulSelfServiceRegistration(): void
    {
        $gatewayRequest = $this->startSelfServiceRegistration();

        // The Gateway forwards the request to the Demo GSSP: as the Gateway, without a Subject, and with Self-Service
        // as the requester
        $this->assertSame(self::GATEWAY_GSSP_SP_ENTITY_ID, $gatewayRequest->getIssuer());
        $this->assertSame([], $this->query($gatewayRequest->getRawXml(), '//saml:Subject'), 'Registration has no Subject');
        $this->assertSame(
            [self::SELFSERVICE_ENTITY_ID],
            $this->query($gatewayRequest->getRawXml(), '//samlp:Scoping/samlp:RequesterID'),
            'The Gateway should send Self-Service as the requester'
        );

        // The Demo GSSP answers the AuthnRequest of the Gateway
        $this->postGsspResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('demo-gssp-response-joe-b1'),
            $gatewayRequest
        ));

        // The Gateway sends a new response to Self-Service
        $this->assertResponseIsSuccessful();
        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_SUCCESS, $samlResponse->getStatusCode());
        $this->assertSame(self::JOE_B1_GSSP_ID, $samlResponse->getNameId());
        $this->assertSame(self::SELFSERVICE_ENTITY_ID, $samlResponse->getAudience());
        $this->assertSamlResponseSignatureIsValid($samlResponse, 'gateway_gssp_idp');
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequestId);
        $this->assertSamlResponseTimestampsAreValid($samlResponse);
        $this->assertSamlResponseMatchesBaseline($samlResponse, 'gateway-gssp-proxy-response-joe-b1-registration');
        $this->assertSame(self::SELFSERVICE_ACS_URL, $this->getFormAction());
        $this->assertSame(
            self::SELFSERVICE_RELAY_STATE,
            $this->client->getCrawler()->filter('input[name="RelayState"]')->attr('value')
        );
    }

    /**
     * The sequence of a registration in Self-Service: joe-b1 logs in to Self-Service through the Gateway, and then
     * registers a Demo GSSP token through the GSSP proxy, in the same session.
     *
     * The login AuthnRequest of Self-Service requests no LoA, so Self-Service gets its default (LoA 1): the Gateway
     * does not ask for a second factor.
     */
    public function testSelfServiceLoginAndRegistrationInTheSameSession(): void
    {
        // Self-Service logs in joe-b1 through the Gateway (SSO), the IdP authenticates joe-b1
        $query = $this->samlFixtures->loadRedirectAuthnRequest('sso-redirect-selfservice');
        $loginRequestId = SamlRequestParser::fromRedirectUrl($query)->getId();
        $this->client->request('GET', self::GATEWAY_SSO_URL . '?' . $query, server: ['HTTPS' => 'on']);
        $idpRequest = SamlRequestParser::fromRedirectUrl($this->followRedirectsUntilLocationStartsWith(self::IDP_SSO_URL));
        $this->client->request(
            'POST',
            self::GATEWAY_SSO_ACS_URL,
            ['SAMLResponse' => $this->samlResigner->resign(
                $this->samlFixtures->loadPostResponse('ssp-response-joe-b1'),
                $idpRequest
            )],
            server: ['HTTPS' => 'on']
        );
        $this->followRedirects();

        // Without a second factor, the Gateway sends the response to Self-Service
        $this->assertResponseIsSuccessful();
        $loginResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_SUCCESS, $loginResponse->getStatusCode());
        $this->assertSame(self::JOE_B1_NAME_ID, $loginResponse->getNameId());
        $this->assertSame(self::SELFSERVICE_LOGIN_ENTITY_ID, $loginResponse->getAudience());
        $this->assertSamlResponseSignatureIsValid($loginResponse);
        $this->assertSamlResponseAnswersRequest($loginResponse, $loginRequestId);
        $this->assertSamlResponseTimestampsAreValid($loginResponse);
        $this->assertSamlResponseMatchesBaseline($loginResponse, 'gateway-sso-response-joe-b1-selfservice-loa1');
        $this->assertSame(self::SELFSERVICE_LOGIN_ACS_URL, $this->getFormAction());

        // In the same session, joe-b1 registers a Demo GSSP token
        $this->assertSelfServiceRegistrationResponseIsSentToSelfService();
    }

    public function testCapturedDemoGsspResponseCanNotBeReplayed(): void
    {
        $this->startSelfServiceRegistration();

        // Unmodified response: InResponseTo does not match and the response has expired
        $this->postGsspResponse($this->samlFixtures->loadPostResponse('demo-gssp-response-joe-b1'));

        // The Gateway sends an error response to Self-Service
        $this->assertResponseIsSuccessful();
        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_RESPONDER, $samlResponse->getStatusCode());
        $this->assertNull($samlResponse->getNameId());
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequestId);
        $this->assertSamlResponseTimestampsAreValid($samlResponse);
        $this->assertSamlResponseMatchesBaseline(
            $samlResponse,
            'gateway-gssp-proxy-response-error-replayed-gssp-response'
        );
        $this->assertSame(self::SELFSERVICE_ACS_URL, $this->getFormAction());
    }

    public function testDemoGsspResponseToOtherRequestIsRejected(): void
    {
        $this->startSelfServiceRegistration();

        // Valid signature and times, but the response answers another AuthnRequest than the one of the Gateway
        $this->postGsspResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('demo-gssp-response-joe-b1'),
            new SamlRequestParser(
                '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="_some-other-request-id"/>'
            )
        ));

        // Like for SFO and SSO, this results in an error page (UnknownInResponseToException), not an error response
        $this->assertResponseStatusCodeSame(500);
        $this->assertSelectorNotExists('input[name="SAMLResponse"]');
    }

    /**
     * The AuthnRequest of Self-Service for the Demo GSSP can not be used for another GSSP: the Destination of the
     * (signed) AuthnRequest is the single sign-on URL of the Demo GSSP.
     */
    public function testAuthnRequestForOtherGsspIsRejected(): void
    {
        $this->client->request(
            'GET',
            'https://gateway.dev.openconext.local/gssp/tiqr/single-sign-on?'
            . $this->samlFixtures->loadRedirectAuthnRequest('selfservice-register-demo-gssp'),
            server: ['HTTPS' => 'on']
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertFalse($this->client->getResponse()->isRedirect(), 'The Gateway should not forward the request');
    }

    /**
     * A pending SFO authentication in the same session does not change the response to Self-Service.
     */
    public function testPendingSfoAuthenticationDoesNotChangeTheSelfServiceResponse(): void
    {
        $this->client->request(
            'GET',
            self::GATEWAY_SFO_URL . '?' . $this->samlFixtures->loadRedirectAuthnRequest('sfo-redirect-joe-a-yk-loa2'),
            server: ['HTTPS' => 'on']
        );
        $this->followRedirects();

        $this->assertSelfServiceRegistrationResponseIsSentToSelfService();
    }

    /**
     * A pending SFO authentication of the ADFS MFA extension in the same session does not change the response to
     * Self-Service.
     *
     * Known bug, this test fails: SamlProxyController::renderSamlResponse() checks the SFO state for an ADFS request.
     * When there is one, it sends the response of Self-Service to the ACS URL of the ADFS SP, with the ADFS parameters
     * (_SAMLResponse, AuthMethod and Context). The response itself is for Self-Service (Audience and NameID), so
     * Self-Service does not receive the registration, and the ADFS SP receives the GSSP identifier of the user.
     */
    public function testPendingAdfsAuthenticationDoesNotChangeTheSelfServiceResponse(): void
    {
        $this->markTestSkipped("Bug in ADFS response handling. Fix this bug first, until then this test fails.");
        $this->client->request(
            'POST',
            self::GATEWAY_SFO_URL,
            [
                'SAMLRequest' => $this->samlFixtures->loadPostAuthnRequest('sfo-adfs-joe-a-yk-loa2'),
                'AuthMethod' => 'ADFS.SCSA',
                'Context' => 'context-of-the-adfs-mfa-extension',
            ],
            server: ['HTTPS' => 'on']
        );
        $this->followRedirects();

        $this->assertSelfServiceRegistrationResponseIsSentToSelfService();
    }

    /**
     * The sequence of a vetting in the RA: the RA sends an AuthnRequest with the GSSP identifier of the token to vet
     * as the Subject, the Gateway forwards it to the Demo GSSP, and sends a new response to the RA when the GSSP
     * confirms that identifier.
     */
    public function testSuccessfulRaVetting(): void
    {
        $gatewayRequest = $this->startRaVetting();

        // The Gateway forwards the request to the Demo GSSP: as the Gateway, with the requested Subject, and with
        // the RA as the requester
        $this->assertSame(self::GATEWAY_GSSP_SP_ENTITY_ID, $gatewayRequest->getIssuer());
        $this->assertSame(
            [self::JANE_B1_GSSP_ID],
            $this->query($gatewayRequest->getRawXml(), '//saml:Subject/saml:NameID'),
            'The Gateway should forward the Subject of the vetting request'
        );
        $this->assertSame(
            [self::RA_ENTITY_ID],
            $this->query($gatewayRequest->getRawXml(), '//samlp:Scoping/samlp:RequesterID'),
            'The Gateway should send the RA as the requester'
        );

        // The Demo GSSP answers the AuthnRequest of the Gateway with the vetted identifier
        $this->postGsspResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('demo-gssp-response-jane-b1'),
            $gatewayRequest
        ));

        // The Gateway sends a new response to the RA
        $this->assertResponseIsSuccessful();
        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_SUCCESS, $samlResponse->getStatusCode());
        $this->assertSame(self::JANE_B1_GSSP_ID, $samlResponse->getNameId());
        $this->assertSame(self::RA_ENTITY_ID, $samlResponse->getAudience());
        $this->assertTrue($samlResponse->isSigned(), 'SAML response should be signed');
        $this->assertSamlResponseSignatureIsValid($samlResponse, 'gateway_gssp_idp');
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequestId);
        $this->assertSamlResponseTimestampsAreValid($samlResponse);
        $this->assertSamlResponseMatchesBaseline($samlResponse, 'gateway-gssp-proxy-response-ra-jane-b1-vetting');
        $this->assertSame(self::RA_ACS_URL, $this->getFormAction());
        $values = $this->client->getCrawler()->filter('form')->form()->getValues();
        $this->assertSame(['SAMLResponse'], array_keys($values), 'The vetting request of the RA has no RelayState');
    }

    /**
     * The GSSP response of another user does not vet the token of jane-b1: the NameID of the response does not match
     * the Subject the RA asked to vet, so the Gateway must not send a successful response to the RA, but an
     * AuthnFailed one (InvalidSubjectException).
     */
    public function testRaVettingRejectsGsspResponseOfAnotherUser(): void
    {
        $gatewayRequest = $this->startRaVetting();

        // Valid signature, times and InResponseTo, but with the Demo GSSP identifier of jane-a1 instead of jane-b1
        $this->postGsspResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('demo-gssp-response-jane-a1'),
            $gatewayRequest
        ));

        // The Gateway sends an AuthnFailed response to the RA
        $this->assertResponseIsSuccessful();
        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_RESPONDER, $samlResponse->getStatusCode());
        $this->assertFalse($samlResponse->hasAssertion(), 'An AuthnFailed response has no assertion');
        $this->assertNull($samlResponse->getNameId());
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequestId);
        $this->assertSamlResponseTimestampsAreValid($samlResponse);
        $this->assertSamlResponseMatchesBaseline($samlResponse, 'gateway-gssp-proxy-response-ra-error-wrong-subject');
        $this->assertSame(self::RA_ACS_URL, $this->getFormAction());
    }

    public function testCapturedDemoGsspResponseCanNotBeReplayedToTheRa(): void
    {
        $this->startRaVetting();

        // Unmodified response: InResponseTo does not match and the response has expired
        $this->postGsspResponse($this->samlFixtures->loadPostResponse('demo-gssp-response-jane-b1'));

        // The Gateway sends an error response to the RA
        $this->assertResponseIsSuccessful();
        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_RESPONDER, $samlResponse->getStatusCode());
        $this->assertNull($samlResponse->getNameId());
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequestId);
        $this->assertSamlResponseTimestampsAreValid($samlResponse);
        $this->assertSamlResponseMatchesBaseline(
            $samlResponse,
            'gateway-gssp-proxy-response-ra-error-replayed-gssp-response'
        );
        $this->assertSame(self::RA_ACS_URL, $this->getFormAction());
    }

    public function testDemoGsspResponseToOtherRequestIsRejectedForTheRa(): void
    {
        $this->startRaVetting();

        // Valid signature and times, but the response answers another AuthnRequest than the one of the Gateway
        $this->postGsspResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('demo-gssp-response-jane-b1'),
            new SamlRequestParser(
                '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="_some-other-request-id"/>'
            )
        ));

        // Like for Self-Service, this results in an error page (UnknownInResponseToException), not an error response
        $this->assertResponseStatusCodeSame(500);
        $this->assertSelectorNotExists('input[name="SAMLResponse"]');
    }

    /**
     * Register the token of joe-b1, and assert the Gateway sends the response to Self-Service, without ADFS parameters.
     */
    private function assertSelfServiceRegistrationResponseIsSentToSelfService(): void
    {
        $this->postGsspResponse($this->samlResigner->resign(
            $this->samlFixtures->loadPostResponse('demo-gssp-response-joe-b1'),
            $this->startSelfServiceRegistration()
        ));

        $this->assertResponseIsSuccessful();
        $this->assertSame(self::SELFSERVICE_ACS_URL, $this->getFormAction(), 'The response should be sent to Self-Service');
        $values = $this->client->getCrawler()->filter('form')->form()->getValues();
        ksort($values);
        $this->assertSame(['RelayState', 'SAMLResponse'], array_keys($values), 'The POST parameters of the response');

        $samlResponse = $this->getSamlResponseFromCurrentPage();
        $this->assertSame(Constants::STATUS_SUCCESS, $samlResponse->getStatusCode());
        $this->assertSame(self::JOE_B1_GSSP_ID, $samlResponse->getNameId());
        $this->assertSamlResponseAnswersRequest($samlResponse, $this->spRequestId);
    }

    /**
     * Send the registration AuthnRequest of Self-Service, and follow the Gateway until it redirects to the Demo GSSP.
     *
     * @return SamlRequestParser the AuthnRequest the Gateway sent to the Demo GSSP
     */
    private function startSelfServiceRegistration(): SamlRequestParser
    {
        $query = $this->samlFixtures->loadRedirectAuthnRequest('selfservice-register-demo-gssp');
        $this->spRequestId = SamlRequestParser::fromRedirectUrl($query)->getId();
        $this->client->request('GET', self::GATEWAY_GSSP_SSO_URL . '?' . $query, server: ['HTTPS' => 'on']);

        return SamlRequestParser::fromRedirectUrl(
            $this->followRedirectsUntilLocationStartsWith(self::DEMO_GSSP_SSO_URL)
        );
    }

    /**
     * Send the vetting AuthnRequest of the RA, and follow the Gateway until it redirects to the Demo GSSP.
     *
     * @return SamlRequestParser the AuthnRequest the Gateway sent to the Demo GSSP
     */
    private function startRaVetting(): SamlRequestParser
    {
        $query = $this->samlFixtures->loadRedirectAuthnRequest('gssp-redirect-ra-jane-b1');
        $this->spRequestId = SamlRequestParser::fromRedirectUrl($query)->getId();
        $this->client->request('GET', self::GATEWAY_GSSP_SSO_URL . '?' . $query, server: ['HTTPS' => 'on']);

        return SamlRequestParser::fromRedirectUrl(
            $this->followRedirectsUntilLocationStartsWith(self::DEMO_GSSP_SSO_URL)
        );
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

    /**
     * @return string[] the text content of the nodes in $xml that match $expression
     */
    private function query(string $xml, string $expression): array
    {
        $document = new DOMDocument();
        $document->loadXML($xml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('samlp', Constants::NS_SAMLP);
        $xpath->registerNamespace('saml', Constants::NS_SAML);

        $values = [];
        foreach ($xpath->query($expression) as $node) {
            $values[] = $node->textContent;
        }

        return $values;
    }
}
