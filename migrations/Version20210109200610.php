<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210109200610 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departments ADD references_export_prefix VARCHAR(255) DEFAULT NULL, ADD skip_blocked_validation_tokens LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE payment_orders ADD booking_date DATETIME DEFAULT NULL, ADD references_exported TINYINT(1) NOT NULL');

        //Normally setting the factually correct field to 1 should be the last action done to a payment order
        //This way we can determine a booking date for old payment orders.
        $this->addSql('UPDATE payment_orders SET booking_date = last_modified WHERE factually_correct = 1 AND exported = 1;');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departments DROP references_export_prefix, DROP skip_blocked_validation_tokens');
        $this->addSql('ALTER TABLE payment_orders DROP booking_date, DROP references_exported');
    }
}
