<?php

namespace Surfnet\StepupGateway\Behat\Repository;

use Exception;

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
        $sql = "INSERT INTO whitelist_entry (`institution`) VALUES (:institution);";
        $stmt = $this->connection->prepare($sql);
        $data = ['institution' => $institution];
        if ($stmt->execute($data)) {
            return $data;
        }

        throw new Exception('Unable add the institution to the whitelist');
    }
}
