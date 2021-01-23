<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210123204819 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE funding_applications_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_d5dc2e961ed32990f91be0a015eda3a8_idx (type), INDEX object_id_d5dc2e961ed32990f91be0a015eda3a8_idx (object_id), INDEX discriminator_d5dc2e961ed32990f91be0a015eda3a8_idx (discriminator), INDEX transaction_hash_d5dc2e961ed32990f91be0a015eda3a8_idx (transaction_hash), INDEX blame_id_d5dc2e961ed32990f91be0a015eda3a8_idx (blame_id), INDEX created_at_d5dc2e961ed32990f91be0a015eda3a8_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP INDEX UNIQ_1C3CC92C9D70482 ON funding_applications');
        $this->addSql('ALTER TABLE funding_applications ADD funding_id_year_part VARCHAR(255) NOT NULL, ADD funding_id_number INT NOT NULL, DROP funding_id, CHANGE external_funding funding_id_external_funding TINYINT(1) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX funding_id_idx ON funding_applications (funding_id_external_funding, funding_id_number, funding_id_year_part)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE funding_applications_audit');
        $this->addSql('DROP INDEX funding_id_idx ON funding_applications');
        $this->addSql('ALTER TABLE funding_applications ADD funding_id VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP funding_id_year_part, DROP funding_id_number, CHANGE funding_id_external_funding external_funding TINYINT(1) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1C3CC92C9D70482 ON funding_applications (funding_id)');
    }
}
