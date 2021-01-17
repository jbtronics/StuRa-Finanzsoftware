<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210117225634 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE funding_applications (id INT AUTO_INCREMENT NOT NULL, applicant_department_id INT DEFAULT NULL, funding_id VARCHAR(64) NOT NULL, external_funding TINYINT(1) NOT NULL, applicant_name VARCHAR(255) NOT NULL, applicant_email VARCHAR(255) NOT NULL, applicant_organisation_name VARCHAR(255) DEFAULT NULL, applicant_phone VARCHAR(255) DEFAULT NULL, requested_amount INT NOT NULL, funding_intention VARCHAR(255) NOT NULL, last_modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, applicant_address_street_number VARCHAR(255) NOT NULL, applicant_address_zip_code VARCHAR(255) NOT NULL, applicant_address_city VARCHAR(255) NOT NULL, explanation_name VARCHAR(255) DEFAULT NULL, explanation_original_name VARCHAR(255) DEFAULT NULL, explanation_mime_type VARCHAR(255) DEFAULT NULL, explanation_size INT DEFAULT NULL, explanation_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', finance_plan_name VARCHAR(255) DEFAULT NULL, finance_plan_original_name VARCHAR(255) DEFAULT NULL, finance_plan_mime_type VARCHAR(255) DEFAULT NULL, finance_plan_size INT DEFAULT NULL, finance_plan_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', UNIQUE INDEX UNIQ_1C3CC92C9D70482 (funding_id), INDEX IDX_1C3CC92CD86C1105 (applicant_department_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE funding_applications ADD CONSTRAINT FK_1C3CC92CD86C1105 FOREIGN KEY (applicant_department_id) REFERENCES departments (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE funding_applications');
    }
}
