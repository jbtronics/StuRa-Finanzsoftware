<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241003195953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the various new fields to the payment_orders table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment_orders ADD submitter_name VARCHAR(255) NOT NULL, ADD submitter_email VARCHAR(255) NOT NULL, ADD supporting_funding_id VARCHAR(255) DEFAULT NULL, ADD supporting_amount INT DEFAULT NULL, ADD invoice_number VARCHAR(255) DEFAULT NULL, ADD customer_number VARCHAR(255) DEFAULT NULL');

        //The contact_email was renamed to submitter_email
        $this->addSql('UPDATE payment_orders SET submitter_email = contact_email');

        //The first_name and last_name fields are now joined together in the submitter_name field
        $this->addSql('UPDATE payment_orders SET submitter_name = CONCAT(first_name, " ", last_name)');

        $this->addSql('ALTER TABLE payment_orders DROP first_name, DROP last_name, DROP contact_email');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment_orders ADD first_name VARCHAR(255) NOT NULL, ADD last_name VARCHAR(255) NOT NULL, ADD contact_email VARCHAR(255) NOT NULL, DROP submitter_name, DROP submitter_email, DROP supporting_funding_id, DROP supporting_amount, DROP invoice_number, DROP customer_number');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
