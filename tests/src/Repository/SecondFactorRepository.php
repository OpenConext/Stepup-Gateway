<?php

namespace Surfnet\StepupGateway\Behat\Repository;

use Exception;
use Ramsey\Uuid\Uuid;
use Surfnet\StepupBundle\Value\VettingType;

/**
 * A poor mans repository, a pdo connection to the test database is established in the constructor
 */
class SecondFactorRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create($nameId, $tokenType, $institution, $identifier = null, bool $selfAsserted = false)
    {
        $uuid = Uuid::uuid4()->toString();

        // If an identifier is not importand, simply use the UUID, otherwise use the provide one
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
            'vettingType' => $selfAsserted ? VettingType::TYPE_SELF_ASSERTED_REGISTRATION: VettingType::TYPE_ON_PREMISE,
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
                vetting_type
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
                :vettingType
            )
SQL;
        $stmt = $this->connection->prepare($sql);
        if ($stmt->execute($data)) {
            return $data;
        }

        throw new Exception('Unable to insert the new second_factor');
    }
}
