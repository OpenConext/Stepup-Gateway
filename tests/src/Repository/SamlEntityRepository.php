<?php

namespace Surfnet\StepupGateway\Behat\Repository;

use Exception;
use Ramsey\Uuid\Uuid;

/**
 * A poor mans repository, a pdo connection to the test database is established in the constructor
 */
class SamlEntityRepository
{
    const SP_ACS_LOCATION = 'https://gateway.stepup.example.com/test/authentication/consume-assertion';

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function createSp($entityId, $certificate, $sfoEnabled = false)
    {
        $uuid = Uuid::uuid4()->toString();
        $type = 'sp';
        $configuration['acs'] = [self::SP_ACS_LOCATION];
        $configuration['public_key'] = $certificate;
        $configuration['loa'] = ['__default__' => 'http://stepup.example.com/assurance/loa1'];
        $configuration['second_factor_only'] = $sfoEnabled;
        $configuration['second_factor_only_nameid_patterns'] = [
            'urn:collab:person:stepup.example.com:admin',
            'urn:collab:person:stepup.example.com:*',
        ];

        $data = [
            'entityId' => $entityId,
            'type' => $type,
            'configuration' => json_encode($configuration),
            'id' => $uuid,
        ];
        $sql = <<<SQL
            INSERT INTO saml_entity (
                `entity_id`,
                `type`,
                `configuration`,
                `id`
            )
            VALUES (
                :entityId, 
                :type, 
                :configuration, 
                :id                
            )
SQL;
        $stmt = $this->connection->prepare($sql);
        if ($stmt->execute($data)) {
            return $data;
        }

        throw new Exception('Unable to insert the new SP saml_entity');
    }
}
