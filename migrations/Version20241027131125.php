<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241027131125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add data structures to track changes to fields in payment orders in a way that endusers can see them.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_orders ADD supporting_funding_date DATE DEFAULT NULL, ADD field_changes JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_orders DROP supporting_funding_date, DROP field_changes');
    }
}
