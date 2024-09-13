<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200929094103 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added comment field for BankAccount.';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE bank_accounts ADD comment LONGTEXT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE bank_accounts DROP comment');
    }
}
