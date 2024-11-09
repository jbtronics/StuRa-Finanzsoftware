<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241109231434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new datastructures to store details about the checks done by the StuRa finance members';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bank_accounts_audit CHANGE diffs diffs JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE confirmation_token_audit CHANGE diffs diffs JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE confirmer_audit CHANGE diffs diffs JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE departments_audit CHANGE diffs diffs JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_orders ADD mathematically_correct_checked TINYINT(1) NOT NULL, ADD mathematically_correct_timestamp DATETIME DEFAULT NULL, ADD mathematically_correct_confirmer_name VARCHAR(255) DEFAULT NULL, ADD mathematically_correct_confirmer_id INT DEFAULT NULL, ADD mathematically_correct_remark LONGTEXT DEFAULT NULL, ADD factually_correct_checked TINYINT(1) NOT NULL, ADD factually_correct_timestamp DATETIME DEFAULT NULL, ADD factually_correct_confirmer_name VARCHAR(255) DEFAULT NULL, ADD factually_correct_confirmer_id INT DEFAULT NULL, ADD factually_correct_remark LONGTEXT DEFAULT NULL');

        //Move the old data from mathematically_correct and factually_correct to the new fields
        $this->addSql('UPDATE payment_orders SET mathematically_correct_checked = mathematically_correct, factually_correct_checked = factually_correct');

        //Drop the old columns
        $this->addSql('ALTER TABLE payment_orders DROP mathematically_correct, DROP factually_correct');
        $this->addSql('ALTER TABLE payment_orders_audit CHANGE diffs diffs JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user_audit CHANGE diffs diffs JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bank_accounts_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE confirmation_token_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE confirmer_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE departments_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_orders ADD mathematically_correct TINYINT(1) NOT NULL, ADD factually_correct TINYINT(1) NOT NULL, DROP mathematically_correct_checked, DROP mathematically_correct_timestamp, DROP mathematically_correct_confirmer_name, DROP mathematically_correct_confirmer_id, DROP mathematically_correct_remark, DROP factually_correct_checked, DROP factually_correct_timestamp, DROP factually_correct_confirmer_name, DROP factually_correct_confirmer_id, DROP factually_correct_remark');
        $this->addSql('ALTER TABLE payment_orders_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_audit CHANGE diffs diffs LONGTEXT DEFAULT NULL');
    }
}
