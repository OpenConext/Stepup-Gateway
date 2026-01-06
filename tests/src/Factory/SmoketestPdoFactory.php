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

namespace Surfnet\StepupGateway\Behat\Factory;

use PDO;

final readonly class SmoketestPdoFactory
{
    public function __construct(
        public string $host,
        public string $user,
        public string $password,
        public string $dbName,
    ) {
    }

    /**
     * Create a PDO connection to the main database for the current environment
     */
    public function createConnection(): PDO
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s', $this->host, $this->dbName);
        $pdo = new PDO($dsn, $this->user, $this->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}

