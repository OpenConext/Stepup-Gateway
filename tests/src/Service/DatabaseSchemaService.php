<?php declare(strict_types=1);

/**
 * Copyright 2025 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupGateway\Behat\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PDO;
use Surfnet\StepupGateway\Behat\Factory\SmoketestPdoFactory;

/**
 * Manages the database schema for smoketests.
 * Uses Doctrine's schema tools to generate SQL from entity mappings,
 * but executes via PDO with elevated privileges (deploy user).
 */
class DatabaseSchemaService
{
    private readonly PDO $connection;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        SmoketestPdoFactory $pdoFactory,
    ) {
        $this->connection = $pdoFactory->createConnection();
    }

    public function dropSchema(): void
    {
        $this->connection->exec('SET FOREIGN_KEY_CHECKS = 0');

        $stmt = $this->connection->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $this->connection->exec("DROP TABLE IF EXISTS `$table`");
        }

        $this->connection->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function createSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $createSql = $schemaTool->getCreateSchemaSql($metadata);

        foreach ($createSql as $sql) {
            $this->connection->exec($sql);
        }
    }

    public function resetSchema(): void
    {
        echo "Preparing test schemas\n";
        $this->dropSchema();
        $this->createSchema();
    }
}

