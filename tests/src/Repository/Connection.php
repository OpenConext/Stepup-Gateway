<?php

namespace Surfnet\StepupGateway\Behat\Repository;

use Doctrine\DBAL\Connection as DBALConnection;
use Exception;
use PDO;
use PDOStatement;

class Connection
{
    /**
     * @var PDO
     */
    private $connection;

    /**
     * @param DBALConnection $connection
     * @throws Exception
     */
    public function __construct(DBALConnection $connection)
    {
        $conn = $connection->getWrappedConnection();
        if (!$conn instanceof PDO) {
            throw new Exception('DBAL Connection should be wrapped around PDO connection');
        }

        $this->connection = $conn;
    }

    /**
     * @param string $statement
     * @return bool|PDOStatement
     */
    public function prepare($statement)
    {
        return $this->connection->prepare($statement);
    }
}
