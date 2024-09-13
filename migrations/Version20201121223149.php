<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201121223149 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE payment_orders ADD confirm1_token VARCHAR(255) DEFAULT NULL, ADD confirm1_timestamp DATETIME DEFAULT NULL, ADD confirm2_token VARCHAR(255) DEFAULT NULL, ADD confirm2_timestamp DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE payment_orders DROP confirm1_token, DROP confirm1_timestamp, DROP confirm2_token, DROP confirm2_timestamp');
    }
}
