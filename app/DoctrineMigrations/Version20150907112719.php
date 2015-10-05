<?php

namespace Surfnet\StepupGateway\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Surfnet\StepupGateway\U2fVerificationBundle\Doctrine\Migrations\Migration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150907112719 extends Migration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(sprintf('ALTER TABLE %s.registration ADD last_used DATETIME NOT NULL, CHANGE key_handle key_handle VARCHAR(255) NOT NULL, CHANGE public_key public_key VARCHAR(255) NOT NULL', $this->getQuotedU2fSchema()));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(sprintf('ALTER TABLE %s.registration DROP last_used, CHANGE key_handle key_handle VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE public_key public_key VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci', $this->getQuotedU2fSchema()));
    }
}
