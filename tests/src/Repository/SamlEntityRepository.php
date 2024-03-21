<?php

namespace Surfnet\StepupGateway\Behat\Repository;

use Exception;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * A poor mans repository, a pdo connection to the test database is established in the constructor
 */
class SamlEntityRepository
{
    const SP_ACS_LOCATION = 'https://gateway.dev.openconext.local/test/authentication/consume-assertion';

    const SP_ADFS_SSO_LOCATION = 'https://gateway.dev.openconext.local/test/authentication/adfs/sso';

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function createSpIfNotExists($entityId, $certificate, $sfoEnabled = false)
    {
        // Does the SP exist?
        $stmt = $this->connection->prepare('SELECT * FROM saml_entity WHERE entity_id=:entityId LIMIT 1');
        $stmt->bindParam('entityId', $entityId);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            // If not, create it
            $uuid = Uuid::uuid4()->toString();
            $type = 'sp';
            $configuration['acs'] = [self::SP_ACS_LOCATION];
            $configuration['public_key'] = $certificate;
            $configuration['loa'] = ['__default__' => 'http://dev.openconext.local/assurance/loa1'];
            $configuration['second_factor_only'] = $sfoEnabled;
            $configuration['set_sso_cookie_on_2fa'] = true;
            $configuration['allow_sso_on_2fa'] = true;
            $configuration['second_factor_only_nameid_patterns'] = [
                'urn:collab:person:stepup.example.com:admin',
                'urn:collab:person:dev.openconext.local:*',
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

            throw new Exception(
                sprintf(
                    'Unable to insert the new SP saml_entity. PDO raised this error: "%s"',
                    $stmt->errorInfo()[2]
               )
            );
        } else {
            // Return the SP data
            $results = $stmt->fetchAll();
            $result = $results[0];
            $data = [
                'entityId' => $result['entity_id'],
                'type' => $result['type'],
                'configuration' => $result['configuration'],
                'id' => $result['id'],
            ];

            return $data;
        }
    }

    public function createIdpIfNotExists($entityId, $certificate)
    {
        // Does the SP exist?
        $stmt = $this->connection->prepare('SELECT * FROM saml_entity WHERE entity_id=:entityId LIMIT 1');
        $stmt->bindParam('entityId', $entityId, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            // If not, create it
            $uuid = Uuid::uuid4()->toString();
            $type = 'idp';

            $configuration['public_key'] = $certificate;

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

            throw new Exception(
                sprintf(
                    'Unable to insert the new SP saml_entity. PDO raised this error: "%s"',
                    $stmt->errorInfo()[2]
                )
            );
        } else {
            // Return the SP data
            $results = $stmt->fetchAll();
            $result = $results[0];
            $data = [
                'entityId' => $result['entity_id'],
                'type' => $result['type'],
                'configuration' => $result['configuration'],
                'id' => $result['id'],
            ];
            return $data;
        }
    }
}
