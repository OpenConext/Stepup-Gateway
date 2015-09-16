<?php

namespace Surfnet\StepupGateway\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Surfnet\StepupGateway\U2fVerificationBundle\Doctrine\Migrations\Migration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150902154411 extends Migration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(sprintf('CREATE TABLE %s.registration (key_handle VARCHAR(255) NOT NULL, public_key VARCHAR(255) NOT NULL, sign_counter INT NOT NULL, PRIMARY KEY(key_handle)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB', $this->getQuotedU2fSchema()));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(sprintf('DROP TABLE %s.registration', $this->getQuotedU2fSchema()));
    }
}
