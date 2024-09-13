<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200928205725 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create Schema for BankAccount infos';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE bank_accounts (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, iban VARCHAR(255) NOT NULL, bic VARCHAR(255) NOT NULL, account_name VARCHAR(255) NOT NULL, last_modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE departments ADD bank_account_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE departments ADD CONSTRAINT FK_16AEB8D412CB990C FOREIGN KEY (bank_account_id) REFERENCES bank_accounts (id)');
        $this->addSql('CREATE INDEX IDX_16AEB8D412CB990C ON departments (bank_account_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE departments DROP FOREIGN KEY FK_16AEB8D412CB990C');
        $this->addSql('DROP TABLE bank_accounts');
        $this->addSql('DROP INDEX IDX_16AEB8D412CB990C ON departments');
        $this->addSql('ALTER TABLE departments DROP bank_account_id');
    }
}
