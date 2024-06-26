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

namespace App\Form;

use App\Entity\Embeddable\PayeeInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PayeeInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('account_owner', TextType::class, [
            'label' => 'bank_info.account_owner.label',
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'bank_info.account_owner.placeholder',
                'autocomplete' => 'off',
            ],
        ]);

        $builder->add('street', TextType::class, [
            'label' => 'bank_info.street.label',
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'bank_info.street.placeholder',
                'autocomplete' => 'off',
            ],
        ]);

        $builder->add('zip_code', TextType::class, [
            'label' => 'bank_info.zip_code.label',
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'bank_info.zip_code.placeholder',
                'autocomplete' => 'off',
            ],
        ]);

        $builder->add('city', TextType::class, [
            'label' => 'bank_info.city.label',
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'bank_info.city.placeholder',
                'autocomplete' => 'off',
            ],
        ]);

        $builder->add('iban', TextType::class, [
            'label' => 'bank_info.iban.label',
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'bank_info.iban.placeholder',
            ],
        ]);

        $builder->add('bic', TextType::class, [
            'label' => 'bank_info.bic.label',
            'required' => false,
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'bank_info.bic.placeholder',
            ],
        ]);

        $builder->add('bank_name', TextType::class, [
            'label' => 'bank_info.bank_name.label',
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'bank_info.bank_name.placeholder',
            ],
        ]);

        /*$builder->add('reference', TextType::class, [
            'label' => 'bank_info.reference.label',
            'required' => false,
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'bank_info.reference.placeholder'
            ]
        ]);*/
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', PayeeInfo::class);
    }
}
