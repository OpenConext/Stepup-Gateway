<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support\Database;

use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates test fixtures in the database.
 * Replaces the Behat FixtureService with a Doctrine DBAL-based version.
 *
 * Important: The saml_entity table has a UNIQUE constraint on (entity_id, type),
 * so we use INSERT OR IGNORE / check existence to avoid conflicts in shared DB.
 */
class FixtureBuilder
{
    private Connection $connection;

    public function __construct(ContainerInterface $container)
    {
        $this->connection = $container->get('doctrine.dbal.gateway_connection');
    }

    /**
     * Register an SP. Uses INSERT OR IGNORE pattern to handle the unique constraint
     * on (entity_id, type) when using a shared database.
     */
    public function registerSp(
        string $entityId,
        string $certificate,
        bool $sfoEnabled = false,
        array $acsUrls = ['https://sp.example.com/acs'],
        array $loaConfig = ['__default__' => 'http://dev.openconext.local/assurance/loa1'],
        array $nameIdPatterns = ['urn:collab:person:dev.openconext.local:*']
    ): array {
        $uuid = Uuid::uuid4()->toString();

        $configuration = [
            'acs' => $acsUrls,
            'public_key' => $certificate,
            'loa' => $loaConfig,
            'assertion_encryption_enabled' => false,
            'second_factor_only' => $sfoEnabled,
            'second_factor_only_nameid_patterns' => $nameIdPatterns,
            'blacklisted_encryption_algorithms' => [],
            // SSO on 2FA settings (enabled by default for testing)
            'set_sso_cookie_on_2fa' => true,
            'allow_sso_on_2fa' => true,
        ];

        // Check if SP already exists (shared database)
        $existing = $this->connection->fetchAssociative(
            'SELECT * FROM saml_entity WHERE entity_id = ? AND type = ?',
            [$entityId, 'sp']
        );

        if ($existing) {
            return [
                'id' => $existing['id'],
                'entityId' => $existing['entity_id'],
                'configuration' => json_decode($existing['configuration'], true),
            ];
        }

        $this->connection->insert('saml_entity', [
            'id' => $uuid,
            'entity_id' => $entityId,
            'type' => 'sp',
            'configuration' => json_encode($configuration),
        ]);

        return ['id' => $uuid, 'entityId' => $entityId, 'configuration' => $configuration];
    }

    /**
     * Register an IdP. Same unique constraint handling as registerSp.
     */
    public function registerIdp(string $entityId, string $certificate): array
    {
        $uuid = Uuid::uuid4()->toString();

        $configuration = [
            'public_key' => $certificate,
        ];

        // Check if IdP already exists
        $existing = $this->connection->fetchAssociative(
            'SELECT * FROM saml_entity WHERE entity_id = ? AND type = ?',
            [$entityId, 'idp']
        );

        if ($existing) {
            return [
                'id' => $existing['id'],
                'entityId' => $existing['entity_id'],
                'configuration' => json_decode($existing['configuration'], true),
            ];
        }

        $this->connection->insert('saml_entity', [
            'id' => $uuid,
            'entity_id' => $entityId,
            'type' => 'idp',
            'configuration' => json_encode($configuration),
        ]);

        return ['id' => $uuid, 'entityId' => $entityId, 'configuration' => $configuration];
    }

    public function registerYubikeyToken(
        string $nameId,
        string $institution,
        bool $selfAsserted = false,
        string $identifier = '00000001'
    ): array {
        return $this->registerToken($nameId, 'yubikey', $institution, $selfAsserted, $identifier);
    }

    /**
     * Register a second factor token.
     * Uses unique UUIDs for id and identity_id to avoid conflicts in shared DB.
     */
    private function registerToken(
        string $nameId,
        string $tokenType,
        string $institution,
        bool $selfAsserted,
        string $identifier
    ): array {
        $uuid = Uuid::uuid4()->toString();
        $identityId = Uuid::uuid4()->toString();

        $this->connection->insert('second_factor', [
            'id' => $uuid,
            'identity_id' => $identityId,
            'name_id' => $nameId,
            'institution' => $institution,
            'display_locale' => 'en_GB',
            'second_factor_id' => $uuid,
            'second_factor_type' => $tokenType,
            'second_factor_identifier' => $identifier,
            'identity_vetted' => $selfAsserted ? 0 : 1,
        ]);

        return [
            'id' => $uuid,
            'identityId' => $identityId,
            'nameId' => $nameId,
            'secondFactorType' => $tokenType,
            'secondFactorId' => $uuid,
        ];
    }

    /**
     * Add institution to whitelist. Uses INSERT OR IGNORE pattern.
     */
    public function whitelist(string $institution): void
    {
        $existing = $this->connection->fetchAssociative(
            'SELECT * FROM whitelist_entry WHERE institution = ?',
            [$institution]
        );

        if (!$existing) {
            $this->connection->insert('whitelist_entry', [
                'institution' => $institution,
            ]);
        }
    }

    /**
     * Enable SSO on 2FA for an institution. Uses INSERT OR REPLACE pattern.
     */
    public function enableSsoOn2fa(string $institution, bool $registrationBypass = false): void
    {
        $existing = $this->connection->fetchAssociative(
            'SELECT * FROM institution_configuration WHERE institution = ?',
            [$institution]
        );

        if ($existing) {
            $this->connection->update('institution_configuration', [
                'sso_on2fa_enabled' => 1,
                'sso_registration_bypass' => $registrationBypass ? 1 : 0,
            ], ['institution' => $institution]);
        } else {
            $this->connection->insert('institution_configuration', [
                'institution' => $institution,
                'sso_on2fa_enabled' => 1,
                'sso_registration_bypass' => $registrationBypass ? 1 : 0,
            ]);
        }
    }

    /**
     * Disable SSO on 2FA for an institution.
     */
    public function disableSsoOn2fa(string $institution): void
    {
        $existing = $this->connection->fetchAssociative(
            'SELECT * FROM institution_configuration WHERE institution = ?',
            [$institution]
        );

        if ($existing) {
            $this->connection->update('institution_configuration', [
                'sso_on2fa_enabled' => 0,
            ], ['institution' => $institution]);
        } else {
            $this->connection->insert('institution_configuration', [
                'institution' => $institution,
                'sso_on2fa_enabled' => 0,
                'sso_registration_bypass' => 0,
            ]);
        }
    }
}
