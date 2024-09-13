<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201112221324 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE departments ADD email_hhv VARCHAR(255) NOT NULL, ADD email_treasurer VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
       $this->addSql('ALTER TABLE departments DROP email_hhv, DROP email_treasurer');
    }
}
