<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200809114711 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment_orders ADD printed_form_name VARCHAR(255) DEFAULT NULL, ADD printed_form_original_name VARCHAR(255) DEFAULT NULL, ADD printed_form_mime_type VARCHAR(255) DEFAULT NULL, ADD printed_form_size INT DEFAULT NULL, ADD printed_form_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', ADD references_name VARCHAR(255) DEFAULT NULL, ADD references_original_name VARCHAR(255) DEFAULT NULL, ADD references_mime_type VARCHAR(255) DEFAULT NULL, ADD references_size INT DEFAULT NULL, ADD references_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payment_orders DROP printed_form_name, DROP printed_form_original_name, DROP printed_form_mime_type, DROP printed_form_size, DROP printed_form_dimensions, DROP references_name, DROP references_original_name, DROP references_mime_type, DROP references_size, DROP references_dimensions');
    }
}
