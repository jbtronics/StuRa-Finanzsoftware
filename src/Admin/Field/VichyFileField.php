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

namespace App\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Vich\UploaderBundle\Form\Type\VichFileType;

/**
 * An custom input to allow file upload in EasyAdmin. Compatible with VichyUpload Bundle.
 */
class VichyFileField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): VichyFileField
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('admin/field/vichy_file.html.twig')
            ->setFormType(VichFileType::class)
            ->setFormTypeOption('allow_delete', false)
            ->setFormTypeOption('required', false)
            ->setTextAlign('center');
    }
}
