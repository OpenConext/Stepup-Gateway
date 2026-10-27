<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support;

use Doctrine\ORM\Tools\SchemaTool;
use SAML2\Compat\ContainerSingleton;
use Surfnet\SamlBundle\Tests\TestSaml2Container;
use Surfnet\StepupGateway\Tests\Functional\Support\Database\DevconfFixtureLoader;
use Surfnet\StepupGateway\Tests\Functional\Support\Database\FixtureBuilder;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlFixtureLoader;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlResponseParser;
use Surfnet\StepupGateway\Tests\Functional\Support\Saml\SamlResponseResigner;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

abstract class GatewayWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected FixtureBuilder $fixtures;
    protected SamlFixtureLoader $samlFixtures;
    protected SamlResponseResigner $samlResigner;

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
