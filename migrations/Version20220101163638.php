<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220101163638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sepa_exports (id INT AUTO_INCREMENT NOT NULL, number_of_payments INT NOT NULL, total_sum INT NOT NULL, sepa_message_id LONGTEXT NOT NULL, initiator_bic LONGTEXT NOT NULL, booking_date DATETIME NOT NULL, description LONGTEXT NOT NULL, comment LONGTEXT NOT NULL, initiator_iban LONGTEXT NOT NULL, group_ulid BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\', last_modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, xml_name VARCHAR(255) DEFAULT NULL, xml_original_name VARCHAR(255) DEFAULT NULL, xml_mime_type VARCHAR(255) DEFAULT NULL, xml_size INT DEFAULT NULL, xml_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sepaexport_payment_order (sepaexport_id INT NOT NULL, payment_order_id INT NOT NULL, INDEX IDX_D6E88FB3FA78CB41 (sepaexport_id), INDEX IDX_D6E88FB3CD592F50 (payment_order_id), PRIMARY KEY(sepaexport_id, payment_order_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sepaexport_payment_order ADD CONSTRAINT FK_D6E88FB3FA78CB41 FOREIGN KEY (sepaexport_id) REFERENCES sepa_exports (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sepaexport_payment_order ADD CONSTRAINT FK_D6E88FB3CD592F50 FOREIGN KEY (payment_order_id) REFERENCES payment_orders (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sepaexport_payment_order DROP FOREIGN KEY FK_D6E88FB3FA78CB41');
        $this->addSql('DROP TABLE sepa_exports');
        $this->addSql('DROP TABLE sepaexport_payment_order');
    }
}
