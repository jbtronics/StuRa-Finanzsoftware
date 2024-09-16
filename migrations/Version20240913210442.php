<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240913210442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduced confirmation tokens for payment orders and new datastructures to store confirmations on the payment orders';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE confirmation_token (id INT AUTO_INCREMENT NOT NULL, hashed_token VARCHAR(255) NOT NULL, last_modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, confirmer_id INT DEFAULT NULL, payment_order_id INT DEFAULT NULL, INDEX IDX_C05FB297EBF3C9A (confirmer_id), INDEX IDX_C05FB297CD592F50 (payment_order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE confirmation_token_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX type_f7cd38908886398b9af454dc2bc337b7_idx (type), INDEX object_id_f7cd38908886398b9af454dc2bc337b7_idx (object_id), INDEX discriminator_f7cd38908886398b9af454dc2bc337b7_idx (discriminator), INDEX transaction_hash_f7cd38908886398b9af454dc2bc337b7_idx (transaction_hash), INDEX blame_id_f7cd38908886398b9af454dc2bc337b7_idx (blame_id), INDEX created_at_f7cd38908886398b9af454dc2bc337b7_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE confirmation_token ADD CONSTRAINT FK_C05FB297EBF3C9A FOREIGN KEY (confirmer_id) REFERENCES confirmer (id)');
        $this->addSql('ALTER TABLE confirmation_token ADD CONSTRAINT FK_C05FB297CD592F50 FOREIGN KEY (payment_order_id) REFERENCES payment_orders (id)');

        //Create dummy confirmers, which take "owns" all the confirmation tokens of the old system
        $this->addSql(<<<SQL
            INSERT INTO confirmer (name, email, comment)
            VALUES (
                    'HHV (Altes System)',
                    'hhv@legacy.invalid',
                    'Repräsentiert die HHV-Bestätigungen aus dem alten System'
                    ),
            (
                    'Kassenwart (Altes System)',
                    'kv@legacy.invalid',
                    'Repräsentiert die Kassenwart-Bestätigungen aus dem alten System'
            )
        SQL);
        
        //For each payment_order create a confirmation token and copy the data from the existing token fields
        //We use the creation date of the payment order for the creation and modification of the token
        //Look up the email_hhv of the associated department and search for the first confirmer with this email
        $this->addSql(<<<SQL
            INSERT INTO confirmation_token (hashed_token, confirmer_id, payment_order_id, last_modified, creation_date)
            SELECT
                payment_orders.confirm1_token,
                (SELECT MIN(id) FROM confirmer WHERE email = 'hhv@legacy.invalid'),
                payment_orders.id,
                payment_orders.creation_date,
                payment_orders.creation_date
            FROM payment_orders
            WHERE payment_orders.confirm1_token IS NOT NULL
        SQL);

        //Do the same for the confirm2_token with the email_treasurer
        $this->addSql(<<<SQL
            INSERT INTO confirmation_token (hashed_token, confirmer_id, payment_order_id, last_modified, creation_date)
            SELECT
                payment_orders.confirm2_token,
                (SELECT MIN(id) FROM confirmer WHERE email = 'kv@legacy.invalid'),
                payment_orders.id,
                payment_orders.creation_date,
                payment_orders.creation_date
            FROM payment_orders
            WHERE payment_orders.confirm2_token IS NOT NULL
        SQL);

        //Add the new fields to the payment_orders table (and delete the obsolete ones later)
        $this->addSql('ALTER TABLE payment_orders ADD required_confirmations INT NOT NULL, ADD confirmation1_timestamp DATETIME DEFAULT NULL, ADD confirmation1_confirmer_name VARCHAR(255) DEFAULT NULL, ADD confirmation1_confirmation_token_id INT DEFAULT NULL, ADD confirmation1_confirmation_overriden TINYINT(1) NOT NULL, ADD confirmation1_remark LONGTEXT DEFAULT NULL, ADD confirmation2_timestamp DATETIME DEFAULT NULL, ADD confirmation2_confirmer_name VARCHAR(255) DEFAULT NULL, ADD confirmation2_confirmation_token_id INT DEFAULT NULL, ADD confirmation2_confirmation_overriden TINYINT(1) NOT NULL, ADD confirmation2_remark LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_orders ADD confirmation1_confirmer_id INT DEFAULT NULL, ADD confirmation2_confirmer_id INT DEFAULT NULL');

        //For each payment_order copy the data from the factually_correct field to the confirmation1_confirmed field
        $this->addSql(<<<SQL
            UPDATE payment_orders
            SET
            confirmation1_timestamp = confirm1_timestamp,
            confirmation1_confirmer_name = IF(confirm1_timestamp IS NULL, NULL, "Struktur-HHV zum Prüfzeitpunkt"),
            confirmation1_confirmer_id = (SELECT MIN(id) FROM confirmer WHERE email = 'hhv@legacy.invalid'),
            confirmation1_remark = IF(confirm1_timestamp IS NULL, NULL, "Sachlich richtig")
        SQL);

        //For each payment_order copy the data from the mathematically_correct field to the confirmation2_confirmed field
        $this->addSql(<<<SQL
            UPDATE payment_orders
            SET
            confirmation2_timestamp = confirm2_timestamp,
            confirmation2_confirmer_name = IF(confirm2_timestamp IS NULL, NULL, "Struktur-Kassenwart zum Prüfzeitpunkt"),
            confirmation2_confirmer_id = (SELECT MIN(id) FROM confirmer WHERE email = 'kv@legacy.invalid'),
            confirmation2_remark = IF(confirm2_timestamp IS NULL, NULL, "Rechnerisch richtig")
        SQL);


        //Each payment_order requires two confirmations (these were created, even if only one real person did it)
        $this->addSql('UPDATE payment_orders SET required_confirmations = 2');

        $this->addSql('ALTER TABLE bank_accounts_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE confirmer_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE departments DROP email_hhv, DROP email_treasurer, CHANGE contact_emails contact_emails JSON NOT NULL, CHANGE skip_blocked_validation_tokens skip_blocked_validation_tokens JSON NOT NULL');
        $this->addSql('ALTER TABLE departments_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE payment_orders DROP confirm1_token, DROP confirm1_timestamp, DROP confirm2_token, DROP confirm2_timestamp, CHANGE printed_form_dimensions printed_form_dimensions LONGTEXT DEFAULT NULL, CHANGE references_dimensions references_dimensions LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_orders_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE reset_password_request CHANGE requested_at requested_at DATETIME NOT NULL, CHANGE expires_at expires_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL, CHANGE backup_codes backup_codes JSON NOT NULL');
        $this->addSql('ALTER TABLE user_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE confirmation_token DROP FOREIGN KEY FK_C05FB297EBF3C9A');
        $this->addSql('ALTER TABLE confirmation_token DROP FOREIGN KEY FK_C05FB297CD592F50');
        $this->addSql('DROP TABLE confirmation_token');
        $this->addSql('DROP TABLE confirmation_token_audit');
        $this->addSql('ALTER TABLE bank_accounts_audit CHANGE diffs diffs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE confirmer_audit CHANGE diffs diffs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE departments ADD email_hhv LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', ADD email_treasurer LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE contact_emails contact_emails JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE skip_blocked_validation_tokens skip_blocked_validation_tokens JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE departments_audit CHANGE diffs diffs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE payment_orders ADD confirm1_token VARCHAR(255) DEFAULT NULL, ADD confirm1_timestamp DATETIME DEFAULT NULL, ADD confirm2_token VARCHAR(255) DEFAULT NULL, ADD confirm2_timestamp DATETIME DEFAULT NULL, DROP required_confirmations, DROP confirmation1_timestamp, DROP confirmation1_confirmer_name, DROP confirmation1_confirmation_token_id, DROP confirmation1_confirmation_overriden, DROP confirmation1_remark, DROP confirmation2_timestamp, DROP confirmation2_confirmer_name, DROP confirmation2_confirmation_token_id, DROP confirmation2_confirmation_overriden, DROP confirmation2_remark, CHANGE printed_form_dimensions printed_form_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE references_dimensions references_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE payment_orders_audit CHANGE diffs diffs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE reset_password_request CHANGE requested_at requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE backup_codes backup_codes JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user_audit CHANGE diffs diffs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE payment_orders DROP confirmation1_confirmer_id, DROP confirmation2_confirmer_id');
    }
}
