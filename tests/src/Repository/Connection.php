<?php

/**
 * Copyright 2020 SURFnet bv
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

namespace Surfnet\StepupGateway\Behat\Repository;

use PDO;
use PDOStatement;

class Connection
{
    /**
     * @var PDO
     */
    private $connection;

    public function __construct(string $appEnv, $dbUser, $dbPassword, $dbName, $hostName)
    {
        if ($appEnv !== 'smoketest') {
            die('Behat tests should only run in smoketest');
        }

        $dsn = 'mysql:host=%s;dbname=%s';
        // Open a PDO connection
        $this->connection = new PDO(sprintf($dsn, $hostName, $dbName), $dbUser, $dbPassword);
    }

    /**
     * @return bool|PDOStatement
     */
    public function prepare($statement)
    {
        return $this->connection->prepare($statement);
    }
}
