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


use App\Entity\PaymentOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PaymentOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('first_name', TextType::class, [
            'label' => 'payment_order.first_name.label',
            'attr' => [
                'placeholder' => 'payment_order.first_name.placeholder'
            ]
        ]);

        $builder->add('last_name', TextType::class, [
            'label' => 'payment_order.last_name.label',
            'attr' => [
                'placeholder' => 'payment_order.last_name.placeholder'
            ]
        ]);

        $builder->add('project_name', TextType::class, [
            'label' => 'payment_order.project_name.label',
            'attr' => [
                'placeholder' => 'payment_order.project_name.placeholder',
            ],
        ]);

        $builder->add('funding_id', TextType::class, [
            'label' => 'payment_order.funding_id.label',
            'help' => 'payment_order.funding_id.help',
            'required' => false,
            'attr' => [
                'placeholder' => 'payment_order.funding_id.placeholder'
            ],
        ]);

        $builder->add('department', DepartmentChoiceType::class, [
            'label' => 'payment_order.department.label',
        ]);

        $builder->add('amount', MoneyType::class, [
            'label' => 'payment_order.amount.label',
            'divisor' => 100,
            'currency' => 'EUR',
            'attr' => [
                'placeholder' => 'payment_order.amount.placeholder'
            ]
        ]);

        $builder->add('bank_info', BankAccountInfoType::class, [
            'label' => false
        ]);

        $builder->add('printed_form_file', VichFileType::class, [
            'label' => 'payment_order.printed_form.label',
            'help' => 'payment_order.printed_form.help',
            'help_html' => true,
        ]);

        $builder->add('references_file', VichFileType::class, [
            'label' => 'payment_order.references.label',
            'help' => 'payment_order.references.help'
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'payment_order.submit'
        ]);
        $builder->add('reset', ResetType::class, [
            'label' => 'payment_order.discard'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', PaymentOrder::class);
    }
}