<?php

namespace Surfnet\StepupGateway\Behat\Repository;

use PDO;
use PDOStatement;

class Connection
{
    /**
     * @var PDO
     */
    private $connection;

    public function __construct($dbUser, $dbPassword, $dbName, $hostName)
    {
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
