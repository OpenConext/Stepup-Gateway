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
use Ramsey\Uuid\Uuid;
use Surfnet\StepupBundle\Value\VettingType;
use Surfnet\StepupGateway\Behat\Factory\SmoketestPdoFactory;

/**
 * A poor mans repository, a pdo connection to the test database is established in the constructor
 */
class SecondFactorRepository
{
    private readonly PDO $connection;

    public function __construct(SmoketestPdoFactory $factory)
    {
        $this->connection = $factory->createConnection();
    }

    public function create($nameId, $tokenType, $institution, bool $selfAsserted = false, $identifier = null)
    {
        $uuid = Uuid::uuid4()->toString();

        // If an identifier is not important, simply use the UUID, otherwise use the provide one
        if (!$identifier) {
            $identifier = $uuid;
        }

        $data = [
            'identityId' => $uuid,
            'nameId' => $nameId,
            'institution' => $institution,
            'secondFactorId' => $uuid,
            'secondFactorType' => $tokenType,
            'secondFactorIdentifier' => $identifier,
            'id' => $uuid,
            'displayLocale' => 'en_GB',
            'identityVetted' => $selfAsserted ? 0 : 1,
        ];
        $sql = <<<SQL
            INSERT INTO second_factor (
                identity_id, 
                name_id, 
                institution, 
                second_factor_id, 
                second_factor_type, 
                second_factor_identifier, 
                id, 
                display_locale,
                identity_vetted
            )
            VALUES (
                :identityId, 
                :nameId, 
                :institution, 
                :secondFactorId, 
                :secondFactorType, 
                :secondFactorIdentifier, 
                :id, 
                :displayLocale,
                :identityVetted
            )
SQL;
        $stmt = $this->connection->prepare($sql);
        if ($stmt->execute($data)) {
            return $data;
        }

        throw new Exception(
            sprintf(
                'Unable to insert the new second_factor. PDO raised this error: "%s"',
                $stmt->errorInfo()[2]
            )
        );
    }

    public function findBy(string $nameId, string $secondFactorType): array
    {
        $sql = 'SELECT * FROM `second_factor` WHERE `name_id` = :nameId AND `second_factor_type` = :type';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'nameId' => $nameId,
            'type' => $secondFactorType
        ]);
        if ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $result;
        }
        throw new Exception(sprintf('Unable to find %s SF token for %s', $secondFactorType, $nameId));
    }

    public function has(string $nameId, string $secondFactorType): bool
    {
        try {
            $this->findBy($nameId, $secondFactorType);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
