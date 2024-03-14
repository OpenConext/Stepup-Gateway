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

use Exception;
use PDO;

/**
 * A poor mans repository, a pdo connection to the test database is established in the constructor
 */
class WhitelistRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $institution
     * @return array
     * @throws Exception
     */
    public function whitelist($institution)
    {
        // Does the whitelist entry exist?
        $stmt = $this->connection->prepare('SELECT * FROM whitelist_entry WHERE institution=:institution LIMIT 1');
        $stmt->bindParam('institution', $institution, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            $sql = "INSERT INTO whitelist_entry (`institution`) VALUES (:institution);";
            $stmt = $this->connection->prepare($sql);
            $data = ['institution' => $institution];
            if ($stmt->execute($data)) {
                return $data;
            }
            throw new Exception(
                sprintf(
                    'Unable ad the institution to the whitelist. PDO raised this error: "%s"',
                    $stmt->errorInfo()[2]
                )
            );
        } else {
            // Return the existing whitelist data
            return $stmt->fetchAll()[0];
        }
    }
}
