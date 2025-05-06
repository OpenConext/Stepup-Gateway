<?php declare(strict_types=1);

/**
 * Copyright 2023 SURFnet bv
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
use function error_get_last;

/**
 * A poor-mans repository, a pdo connection to the test database is established in the constructor
 */
class InstitutionConfigurationRepository
{

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function configure(string $institution, string $option, bool $value)
    {
        // Does the SP exist?
        $stmt = $this->connection->prepare('SELECT * FROM institution_configuration WHERE institution=:institution LIMIT 1');
        $stmt->bindParam('institution', $institution);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            // If not, create new InstitutionConfiguration
            $data = [
                'institution' => $institution,
                'sso_on2fa_enabled' => $value,
                'sso_registration_bypass' => $value,
            ];
            $sql = <<<SQL
                INSERT INTO institution_configuration (institution, sso_on2fa_enabled, sso_registration_bypass)
                VALUES (:institution, :sso_on2fa_enabled, :sso_registration_bypass);
SQL;
            $stmt = $this->connection->prepare($sql);
            if ($stmt->execute($data)) {
                return $data;
            }
            throw new Exception(
                sprintf(
                    'Unable to insert the new institution_configuration (%s). PDO raised this error: "%s"',
                    $stmt->queryString,
                    $stmt->errorInfo()[2]
                )
            );
        } else {
            $data = [
                'institution' => $institution,
                'value' => $value,
            ];
            $sql = <<<SQL
                update institution_configuration
                set `$option` = :value
                where institution = :institution
            ;
SQL;
            $stmt = $this->connection->prepare($sql);
            if ($stmt->execute($data)) {
                return $data;
            }
            return $data;
        }
    }
}
