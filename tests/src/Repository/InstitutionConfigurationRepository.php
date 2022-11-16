<?php declare(strict_types=1);

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
            ];
            $sql = <<<SQL
                INSERT INTO institution_configuration (institution, sso_on2fa_enabled)
                VALUES (:institution, :sso_on2fa_enabled);
SQL;
            $stmt = $this->connection->prepare($sql);
            if ($stmt->execute($data)) {
                return $data;
            }
            throw new Exception(sprintf('Unable to insert the new institution_configuration (%s)', $stmt->queryString));
        } else {
            $data = [
                'institution' => $institution,
                'option' => $option,
                'value' => $value,
            ];
            $sql = <<<SQL
                update gateway.institution_configuration
                set :option = :value
                where institutiton = :institution
            );
SQL;
            $stmt = $this->connection->prepare($sql);
            if ($stmt->execute($data)) {
                return $data;
            }
            return $data;
        }
    }
}
