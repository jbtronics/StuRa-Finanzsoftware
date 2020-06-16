<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200616195737 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE departments (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, blocked TINYINT(1) NOT NULL, last_modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_orders (id INT AUTO_INCREMENT NOT NULL, department_id INT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, project_name VARCHAR(255) NOT NULL, amount INT NOT NULL, mathematically_correct TINYINT(1) NOT NULL, factually_correct TINYINT(1) NOT NULL, last_modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, bank_info_account_owner VARCHAR(255) NOT NULL, bank_info_street VARCHAR(255) NOT NULL, bank_info_zip_code VARCHAR(255) NOT NULL, bank_info_city VARCHAR(255) NOT NULL, bank_info_iban VARCHAR(255) NOT NULL, bank_info_bic VARCHAR(255) NOT NULL, bank_info_bank_name VARCHAR(255) NOT NULL, bank_info_string VARCHAR(255) NOT NULL, INDEX IDX_C0176678AE80F5DF (department_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment_orders ADD CONSTRAINT FK_C0176678AE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment_orders DROP FOREIGN KEY FK_C0176678AE80F5DF');
        $this->addSql('DROP TABLE departments');
        $this->addSql('DROP TABLE payment_orders');
    }
}
