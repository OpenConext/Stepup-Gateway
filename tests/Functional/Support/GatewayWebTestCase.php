<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Tools\SchemaTool;
use SAML2\Compat\ContainerSingleton;
use Surfnet\SamlBundle\Tests\TestSaml2Container;
use Surfnet\StepupGateway\Tests\Functional\Support\Database\DevconfFixtureLoader;
use Surfnet\StepupGateway\Tests\Functional\Support\Database\FixtureBuilder;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlFixtureLoader;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlResponseParser;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlResponseNormalizer;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlResponseResigner;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlSignatureVerifier;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

abstract class GatewayWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected FixtureBuilder $fixtures;
    protected SamlFixtureLoader $samlFixtures;
    protected SamlResponseResigner $samlResigner;

    /** The time the test started, the lower bound for the timestamps in the responses of the Gateway */
    private DateTimeImmutable $testStartedAt;

    /** The longest validity (NotBefore to NotOnOrAfter) of an assertion the Gateway issues */
    private const MAX_ASSERTION_VALIDITY_SECONDS = 300;

    /**
     * Use devconf fixtures (captured from devconf environment) instead of
     * generating schema from scratch. Set to false to use SchemaTool.
     */
    protected static bool $useDevconfFixtures = true;

    /**
     * The Symfony environment for functional tests.
     * Set via APP_ENV=functional in ci/qa/phpunit-functional.xml
     */
    protected static function getKernelClass(): string
    {
        return 'App\Kernel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->testStartedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        // Create client with functional environment
        // APP_ENV is set in ci/qa/phpunit-functional.xml
        $this->client = static::createClient([
            'environment' => 'functional',
            'debug' => false,
        ]);

        // The in-memory SQLite database lives in the kernel; keep the same kernel across requests
        $this->client->disableReboot();

        // Set up SAML2 container for the test
        ContainerSingleton::setContainer(
            new TestSaml2Container(static::getContainer()->get('logger'))
        );

        // Set up database (shared across tests in the same class)
        $this->setupDatabase();

        // Initialize helpers
        $this->fixtures = new FixtureBuilder(static::getContainer());
        $this->samlFixtures = new SamlFixtureLoader(__DIR__ . '/../Fixtures');
        $this->samlResigner = new SamlResponseResigner(__DIR__ . '/../Fixtures');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Set up the database. The in-memory database is created per kernel, so this runs for every test.
     *
     * Two strategies:
     * 1. Load devconf fixture (preferred) - real-world data from devconf
     * 2. Create schema from entity metadata (fallback) - clean schema
     */
    private function setupDatabase(): void
    {
        $container = static::getContainer();

        if (static::$useDevconfFixtures) {
            // Load database fixture captured from devconf
            $loader = new DevconfFixtureLoader(
                $container,
                __DIR__ . '/../Fixtures'
            );
            $loader->load();
        } else {
            // Create schema from entity metadata
            $em = $container->get('doctrine')->getManager();
            $schemaTool = new SchemaTool($em);
            $metadata = $em->getMetadataFactory()->getAllMetadata();

            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

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
     * Assert the SAML response is signed by the gateway IdP key (gateway_idp.crt) and the signature is valid.
     */
    protected function assertSamlResponseSignatureIsValid(SamlResponseParser $samlResponse): void
    {
        try {
            $signed = (new SamlSignatureVerifier())->verify(
                $samlResponse->getRawXml(),
                __DIR__ . '/../Fixtures/devconf/keys/gateway_idp.crt'
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
     *  - The SubjectConfirmation and session do not expire before the assertion does
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
     * Expect the Yubikey OTP form on the current page and submit it (the mocked Yubikey service accepts any OTP).
     */
    protected function submitYubikeyOtp(): void
    {
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'YubiKey');

        $form = $this->client->getCrawler()->selectButton('gateway_verify_yubikey_otp_submit')->form();
        $form['gateway_verify_yubikey_otp[otp]'] = 'bogus-otp-we-use-a-mock-yubikey-service';
        $this->client->submit($form);
    }

    /**
     * Get the form action URL from the current page
     */
    protected function getFormAction(): string
    {
        return $this->client->getCrawler()->filter('form')->attr('action');
    }

    /**
     * Load a certificate from the devconf keys directory.
     */
    protected function loadDevconfCertificate(string $name): string
    {
        $path = __DIR__ . '/../Fixtures/devconf/keys/' . $name . '.crt';
        if (!file_exists($path)) {
            // Fallback to generated keys
            $path = __DIR__ . '/../Fixtures/keys/' . $name . '.crt';
        }
        $cert = file_get_contents($path);
        return str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r"],
            '',
            $cert
        );
    }

    /**
     * Load a certificate from the Keys directory and return the base64 content.
     */
    protected function loadCertificate(string $name): string
    {
        $path = __DIR__ . '/../Fixtures/keys/' . $name . '.crt';
        $cert = file_get_contents($path);
        return str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r"],
            '',
            $cert
        );
    }

    /**
     * Load a private key path from the Keys directory.
     */
    protected function getPrivateKeyPath(string $name): string
    {
        return __DIR__ . '/../Fixtures/keys/' . $name . '.key';
    }
}
