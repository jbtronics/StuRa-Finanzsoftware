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

namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;

class PasswordChangeType extends AbstractType
{
    public function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('old_password', PasswordType::class, [
            'label' => 'password.old',
            'constraints' => [new UserPassword()],
        ]);

        $builder->add('plain_password', RepeatedType::class, [
            'label' => false,
            'type' => PasswordType::class,
            'first_options' => [
                'label' => 'password.new',
            ],
            'second_options' => [
                'label' => 'password.repeat',
            ],
            'constraints' => [new Length([
                'min' => 6,
            ])],
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'submit',
            'attr' => [
                'class' => 'offset-sm-3 btn-primary',
            ],
        ]);
    }
}
