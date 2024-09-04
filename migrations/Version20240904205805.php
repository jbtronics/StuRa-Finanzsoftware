<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240904205805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE confirmer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, comment LONGTEXT NOT NULL, last_modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE confirmer_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_be49749629d32272a2096d33404c06e1_idx (type), INDEX object_id_be49749629d32272a2096d33404c06e1_idx (object_id), INDEX discriminator_be49749629d32272a2096d33404c06e1_idx (discriminator), INDEX transaction_hash_be49749629d32272a2096d33404c06e1_idx (transaction_hash), INDEX blame_id_be49749629d32272a2096d33404c06e1_idx (blame_id), INDEX created_at_be49749629d32272a2096d33404c06e1_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE departments_confirmers (department_id INT NOT NULL, confirmer_id INT NOT NULL, INDEX IDX_5F6563A2AE80F5DF (department_id), INDEX IDX_5F6563A2EBF3C9A (confirmer_id), PRIMARY KEY(department_id, confirmer_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE departments_confirmers ADD CONSTRAINT FK_5F6563A2AE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE departments_confirmers ADD CONSTRAINT FK_5F6563A2EBF3C9A FOREIGN KEY (confirmer_id) REFERENCES confirmer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bank_accounts_audit CHANGE diffs diffs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE departments DROP email_hhv, DROP email_treasurer, CHANGE contact_emails contact_emails JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE skip_blocked_validation_tokens skip_blocked_validation_tokens JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE departments_audit CHANGE diffs diffs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE payment_orders_audit CHANGE diffs diffs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE backup_codes backup_codes JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user_audit CHANGE diffs diffs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departments_confirmers DROP FOREIGN KEY FK_5F6563A2AE80F5DF');
        $this->addSql('ALTER TABLE departments_confirmers DROP FOREIGN KEY FK_5F6563A2EBF3C9A');
        $this->addSql('DROP TABLE confirmer');
        $this->addSql('DROP TABLE confirmer_audit');
        $this->addSql('DROP TABLE departments_confirmers');
        $this->addSql('ALTER TABLE bank_accounts_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE departments ADD email_hhv LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', ADD email_treasurer LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE contact_emails contact_emails JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE skip_blocked_validation_tokens skip_blocked_validation_tokens JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE departments_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_orders_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE backup_codes backup_codes JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL');
    }
}
