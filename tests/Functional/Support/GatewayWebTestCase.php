<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support;

use DateTimeImmutable;
use DateTimeZone;
use SAML2\Compat\ContainerSingleton;
use Surfnet\SamlBundle\Tests\TestSaml2Container;
use Surfnet\StepupGateway\Tests\Functional\Support\Database\DevconfFixtureLoader;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlFixtureLoader;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlResponseParser;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlResponseNormalizer;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlResponseResigner;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlSignatureVerifier;
use Surfnet\StepupGateway\Tests\Functional\Support\TestDouble\SmsService;
use Surfnet\StepupGateway\Tests\Functional\Support\TestDouble\YubikeyService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

abstract class GatewayWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected SamlFixtureLoader $samlFixtures;
    protected SamlResponseResigner $samlResigner;

    /** The time the test started, the lower bound for the timestamps in the responses of the Gateway */
    private DateTimeImmutable $testStartedAt;

    /** The public IDs (second_factor_identifier in database.sql) of the Yubikeys of the devconf users */
    protected const ADMIN_YUBIKEY_ID = '00000001';
    protected const JOE_A_YK_YUBIKEY_ID = '00000002';

    /** The phone number (MSISDN) of the SMS token of joe-a-sms, "+31 (0) 612345678" in database.sql */
    protected const JOE_A_SMS_MSISDN = '31612345678';

    /** The phone number (MSISDN) of the SMS token of jane-a-sms, "+31 (0) 633333333" in database.sql */
    protected const JANE_A_SMS_MSISDN = '31633333333';

    /** The longest validity (NotBefore to NotOnOrAfter) of an assertion the Gateway issues */
    private const MAX_ASSERTION_VALIDITY_SECONDS = 300;

    protected static function getKernelClass(): string
    {
        return 'App\Kernel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->testStartedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        // The client reboots the kernel before every request, so each request runs on a fresh kernel and
        // Symfony container, like in production
        $this->client = static::createClient([
            'environment' => 'functional',
            'debug' => false,
        ]);

        // Set up SAML2 container for the test
        ContainerSingleton::setContainer(
            new TestSaml2Container(static::getContainer()->get('logger'))
        );

        // The SMS test double keeps the sent messages in a static property (every request gets a new instance of
        // the service, as the kernel reboots); start every test without messages
        SmsService::reset();

        // Set up database (recreated for every test, see setupDatabase)
        $this->setupDatabase();

        // Initialize helpers
        $this->samlFixtures = new SamlFixtureLoader(__DIR__ . '/../Fixtures');
        $this->samlResigner = new SamlResponseResigner(__DIR__ . '/../Fixtures');
    }

    /**
     * Set up the database. The functional environment uses a file-backed SQLite database (it survives the kernel
     * reboot before every request), which is recreated here for every test: the schema is dropped and created, and
     * the devconf fixture is loaded. So a test that modifies rows cannot leak into other tests.
     */
    private function setupDatabase(): void
    {
        $loader = new DevconfFixtureLoader(
            static::getContainer(),
            __DIR__ . '/../Fixtures'
        );
        $loader->load();
    }

    /**
     * Get the SAML response from the current response (auto-submit form)
     */
    protected function getSamlResponseFromCurrentPage(): SamlResponseParser
    {
        $crawler = $this->client->getCrawler();
        $samlResponseInput = $crawler->filter('input[name="SAMLResponse"]');

        if ($samlResponseInput->count() === 0) {
            // Try ADFS variant
            $samlResponseInput = $crawler->filter('input[name="_SAMLResponse"]');
        }

        $this->assertGreaterThan(0, $samlResponseInput->count(), 'No SAMLResponse found in response');
        $base64Response = $samlResponseInput->attr('value');

        return new SamlResponseParser($base64Response);
    }

    /**
     * Assert the SAML response is signed with the key of $certificateName (Fixtures/devconf/keys/<name>.crt) and the
     * signature is valid. By default the gateway IdP key (gateway_idp.crt), that signs the SSO and SFO responses. The
     * responses of the GSSP proxy to Self-Service and the RA are signed with the gateway GSSP IdP key
     * (gateway_gssp_idp).
     */
    protected function assertSamlResponseSignatureIsValid(
        SamlResponseParser $samlResponse,
        string $certificateName = 'gateway_idp',
    ): void {
        try {
            $signed = (new SamlSignatureVerifier())->verify(
                $samlResponse->getRawXml(),
                __DIR__ . '/../Fixtures/devconf/keys/' . $certificateName . '.crt'
            );
        } catch (RuntimeException $e) {
            $this->fail('SAML response signature verification failed: ' . $e->getMessage());
        }

        $this->assertNotEmpty($signed);
    }

    /**
     * Assert the SAML response answers the AuthnRequest of the SP: the InResponseTo of the Response and (when it has
     * an assertion) of the SubjectConfirmationData is the ID of that request.
     */
    protected function assertSamlResponseAnswersRequest(SamlResponseParser $samlResponse, string $requestId): void
    {
        $this->assertNotSame('', $requestId, 'The request ID of the SP is unknown');
        $this->assertSame($requestId, $samlResponse->getInResponseTo(), 'Response InResponseTo');

        if ($samlResponse->hasAssertion()) {
            $this->assertSame(
                $requestId,
                $samlResponse->getSubjectConfirmationInResponseTo(),
                'SubjectConfirmationData InResponseTo'
            );
        }
    }

    /**
     * Assert the timestamps in the SAML response are correct for a response the Gateway just issued:
     *  - IssueInstant of the Response and Assertion, and AuthnInstant, are between the start of the test and now
     *  - The assertion is valid now (NotBefore <= now < NotOnOrAfter), for at most MAX_ASSERTION_VALIDITY_SECONDS
     *  - The SubjectConfirmationData does not outlive the assertion Conditions, and the session has not expired
     */
    protected function assertSamlResponseTimestampsAreValid(SamlResponseParser $samlResponse): void
    {
        $now = time();
        // SAML timestamps have a precision of a second
        $start = $this->testStartedAt->getTimestamp();

        $issueInstant = $samlResponse->getResponseIssueInstant();
        $this->assertNotNull($issueInstant, 'Response IssueInstant');
        $this->assertTimeBetween($start, $now, $issueInstant, 'Response IssueInstant');

        if (!$samlResponse->hasAssertion()) {
            return;
        }

        $assertionIssueInstant = $samlResponse->getAssertionIssueInstant();
        $authnInstant = $samlResponse->getAuthnInstant();
        $notBefore = $samlResponse->getConditionsNotBefore();
        $notOnOrAfter = $samlResponse->getConditionsNotOnOrAfter();
        $confirmationNotOnOrAfter = $samlResponse->getSubjectConfirmationNotOnOrAfter();

        $this->assertNotNull($assertionIssueInstant, 'Assertion IssueInstant');
        $this->assertNotNull($authnInstant, 'AuthnInstant');
        $this->assertNotNull($notBefore, 'Conditions NotBefore');
        $this->assertNotNull($notOnOrAfter, 'Conditions NotOnOrAfter');
        $this->assertNotNull($confirmationNotOnOrAfter, 'SubjectConfirmationData NotOnOrAfter');

        $this->assertTimeBetween($start, $now, $assertionIssueInstant, 'Assertion IssueInstant');
        $this->assertTimeBetween($start, $now, $authnInstant, 'AuthnInstant');

        $this->assertLessThanOrEqual($now, $notBefore->getTimestamp(), 'Conditions NotBefore is in the future');
        $this->assertGreaterThan($now, $notOnOrAfter->getTimestamp(), 'Conditions NotOnOrAfter has passed');
        $this->assertGreaterThan($now, $confirmationNotOnOrAfter->getTimestamp(), 'SubjectConfirmationData has expired');
        $this->assertLessThanOrEqual(
            $notBefore->getTimestamp() + self::MAX_ASSERTION_VALIDITY_SECONDS,
            $notOnOrAfter->getTimestamp(),
            'The assertion is valid for too long'
        );
        $this->assertLessThanOrEqual(
            $notOnOrAfter->getTimestamp(),
            $confirmationNotOnOrAfter->getTimestamp(),
            'SubjectConfirmationData outlives the assertion conditions'
        );

        $sessionNotOnOrAfter = $samlResponse->getSessionNotOnOrAfter();
        if ($sessionNotOnOrAfter !== null) {
            $this->assertGreaterThan($now, $sessionNotOnOrAfter->getTimestamp(), 'The session has expired');
        }
    }

    private function assertTimeBetween(int $start, int $end, DateTimeImmutable $time, string $description): void
    {
        $this->assertGreaterThanOrEqual($start, $time->getTimestamp(), $description . ' is before the test started');
        $this->assertLessThanOrEqual($end, $time->getTimestamp(), $description . ' is in the future');
    }

    /**
     * Assert the SAML response has the same structure as the baseline fixture (Fixtures/saml/baselines/<name>.php).
     *
     * IDs, timestamps, InResponseTo and the signature values are replaced by placeholders before comparing, see
     * SamlResponseNormalizer. Run the tests with UPDATE_SAML_BASELINES=1 to (re)create the baseline fixture when the
     * response differs from it, and review the changes in git.
     */
    protected function assertSamlResponseMatchesBaseline(SamlResponseParser $samlResponse, string $baselineName): void
    {
        $normalizer = new SamlResponseNormalizer();
        $actual = $normalizer->normalize($samlResponse->getRawXml());
        $expected = $this->loadNormalizedBaseline($normalizer, $baselineName);

        if (getenv('UPDATE_SAML_BASELINES') === '1') {
            if ($expected !== $actual) {
                $this->updateBaseline($samlResponse, $baselineName);
            }
            $this->addToAssertionCount(1);

            return;
        }

        if ($expected === null) {
            $this->fail(sprintf(
                'Baseline SAML response "%s" does not exist. Run the tests with UPDATE_SAML_BASELINES=1 to create it.',
                $baselineName
            ));
        }

        $this->assertSame(
            $expected,
            $actual,
            sprintf(
                'The SAML response differs from baseline "%s". If the change is intended, run the tests with '
                . 'UPDATE_SAML_BASELINES=1 to update the baseline.',
                $baselineName
            )
        );
    }

    private function loadNormalizedBaseline(SamlResponseNormalizer $normalizer, string $baselineName): ?string
    {
        try {
            $baseline = $this->samlFixtures->loadBaselineResponse($baselineName);
        } catch (RuntimeException) {
            return null;
        }

        return $normalizer->normalize(base64_decode($baseline, true));
    }

    /**
     * Write the response as baseline fixture, using the same converter as for captured messages.
     */
    private function updateBaseline(SamlResponseParser $samlResponse, string $baselineName): void
    {
        $messageFile = tempnam(sys_get_temp_dir(), 'saml-baseline');
        file_put_contents($messageFile, $samlResponse->getBase64Response());

        $command = sprintf(
            '%s %s --type=response-baseline --file %s %s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/../bin/convert-saml-to-fixture'),
            escapeshellarg($messageFile),
            escapeshellarg($baselineName)
        );
        exec($command, $output, $exitCode);
        unlink($messageFile);

        if ($exitCode !== 0) {
            $this->fail(sprintf("Updating baseline \"%s\" failed:\n%s", $baselineName, implode("\n", $output)));
        }

        fwrite(STDERR, sprintf("\nUpdated SAML response baseline: %s\n", $baselineName));
    }

    /**
     * Follow redirects (at most $max) until the current response is not a redirect.
     */
    protected function followRedirects(int $max = 6): void
    {
        for ($i = 0; $i < $max && $this->client->getResponse()->isRedirect(); $i++) {
            $this->client->followRedirect();
        }
    }

    /**
     * Follow redirects (at most $max) until the gateway redirects to a location that starts with $prefix. That
     * redirect is not followed.
     *
     * @return string the location the gateway redirects to
     */
    protected function followRedirectsUntilLocationStartsWith(string $prefix, int $max = 6): string
    {
        for ($i = 0; $i < $max; $i++) {
            $this->assertResponseRedirects();
            $location = $this->client->getResponse()->headers->get('Location');
            if (str_starts_with($location, $prefix)) {
                return $location;
            }
            $this->client->followRedirect();
        }

        $this->fail(sprintf('The gateway did not redirect to "%s"', $prefix));
    }

    /**
     * Expect the Yubikey OTP form on the current page and submit an OTP of the Yubikey with $yubikeyPublicId (e.g.
     * JOE_A_YK_YUBIKEY_ID). The Yubikey service of the functional tests accepts the OTP without calling Yubico, but the
     * Gateway still checks that the public ID in the OTP matches the Yubikey of the user, see TestDouble\YubikeyService.
     */
    protected function submitYubikeyOtp(string $yubikeyPublicId): void
    {
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'YubiKey');

        $form = $this->client->getCrawler()->selectButton('gateway_verify_yubikey_submit')->form();
        $form['gateway_verify_yubikey[yubikeyInput]'] = YubikeyService::otpForPublicId($yubikeyPublicId);
        $this->client->submit($form);
    }

    /**
     * Authenticate with the SMS token whose phone number is $msisdn (e.g. JOE_A_SMS_MSISDN): expect the page to enter
     * the SMS OTP on the current page, and submit the OTP the Gateway sent. The Gateway sends the SMS when it redirects
     * to the SMS verification (/verify-second-factor/sms/send-challenge), without user interaction.
     */
    protected function submitSmsOtp(string $msisdn): void
    {
        $this->assertSmsOtpFormIsShown();
        $this->submitSmsChallenge($this->getSmsOtpSentTo($msisdn));
    }

    protected function assertSmsOtpFormIsShown(): void
    {
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="gateway_verify_sms_challenge"]');
    }

    /**
     * Expect the page to enter the SMS OTP on the current page, and submit $otp.
     */
    protected function submitSmsChallenge(string $otp): void
    {
        $form = $this->client->getCrawler()->filter('form[name="gateway_verify_sms_challenge"]')->form();
        $form['gateway_verify_sms_challenge[challenge]'] = $otp;
        $this->client->submit($form);
    }

    /**
     * The OTP of the last SMS the Gateway sent to $msisdn, see TestDouble\SmsService.
     */
    protected function getSmsOtpSentTo(string $msisdn): string
    {
        $smsService = static::getContainer()->get('gateway.service.gateway_api_sms');
        $this->assertInstanceOf(SmsService::class, $smsService);

        return $smsService->getOtpSentTo($msisdn);
    }

    /**
     * Get the form action URL from the current page
     */
    protected function getFormAction(): string
    {
        return $this->client->getCrawler()->filter('form')->attr('action');
    }
}
