<?php
/*
 * Copyright (C) 2020  Jan Böhmer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
        $this->addSql('ALTER TABLE payment_orders ADD printed_form_name VARCHAR(255) DEFAULT NULL, ADD printed_form_original_name VARCHAR(255) DEFAULT NULL, ADD printed_form_mime_type VARCHAR(255) DEFAULT NULL, ADD printed_form_size INT DEFAULT NULL, ADD printed_form_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', ADD references_name VARCHAR(255) DEFAULT NULL, ADD references_original_name VARCHAR(255) DEFAULT NULL, ADD references_mime_type VARCHAR(255) DEFAULT NULL, ADD references_size INT DEFAULT NULL, ADD references_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE payment_orders DROP printed_form_name, DROP printed_form_original_name, DROP printed_form_mime_type, DROP printed_form_size, DROP printed_form_dimensions, DROP references_name, DROP references_original_name, DROP references_mime_type, DROP references_size, DROP references_dimensions');
    }
}
