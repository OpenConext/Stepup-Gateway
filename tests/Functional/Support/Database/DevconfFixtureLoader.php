<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support\Database;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads database fixtures captured from the devconf environment.
 * Replaces the schema creation with loading a pre-captured SQLite dump.
 */
class DevconfFixtureLoader
{
    private Connection $connection;
    private EntityManagerInterface $entityManager;
    private string $fixturesPath;

    public function __construct(ContainerInterface $container, string $fixturesPath)
    {
        $this->connection = $container->get('doctrine.dbal.gateway_connection');
        $this->entityManager = $container->get('doctrine.orm.gateway_entity_manager');
        $this->fixturesPath = $fixturesPath;
    }

    /**
     * Load the database fixture captured from devconf.
     * This replaces the SchemaTool-based schema creation.
     */
    public function load(): void
    {
        $sqlFile = $this->fixturesPath . '/devconf/database.sql';

        if (!file_exists($sqlFile)) {
            throw new \RuntimeException(sprintf(
                'Devconf database fixture not found: %s. Run: php tests/Functional/bin/capture-devconf-fixture --devconf-path=/path/to/OpenConext-devconf',
                $sqlFile
            ));
        }

        // The dump only contains data, so create the schema from the entity metadata first.
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $sql = file_get_contents($sqlFile);

        // Split into individual statements and execute
        $statements = $this->splitSqlStatements($sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || str_starts_with($statement, '--')) {
                continue;
            }

            try {
                $this->connection->executeStatement($statement);
            } catch (\Exception $e) {
                // Ignore "table already exists" errors for idempotency
                if (!str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Split SQL dump into individual statements.
     */
    private function splitSqlStatements(string $sql): array
    {
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        // Remove MariaDB conditional comments (e.g. /*M!999999\- enable the sandbox mode */)
        $sql = preg_replace('#/\*.*?\*/#s', '', $sql);

        // Split on semicolons (naive but works for our dump)
        $statements = explode(';', $sql);

        return array_filter(array_map('trim', $statements));
    }
}
