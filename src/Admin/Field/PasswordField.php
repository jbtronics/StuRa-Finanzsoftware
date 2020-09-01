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


use Doctrine\DBAL\Types\TextType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

/**
 * Custom field for a Password and Repeat password input in EasyAdmin
 * @package App\Admin\Field
 */
class PasswordField implements FieldInterface
{

    use FieldTrait;


    public static function new(string $propertyName, ?string $label = null)
    {
        return (new self())
            ->setProperty($propertyName)
            ->setFormType(RepeatedType::class)
            ->setTemplateName('crud/field/text')
            ->setFormTypeOptions([
                                     'type' => PasswordType::class,
                                     'first_options' =>  ['label' => 'password.new'],
                                     'second_options' => ['label' => 'password.repeat'],
                                 ]);
    }
}