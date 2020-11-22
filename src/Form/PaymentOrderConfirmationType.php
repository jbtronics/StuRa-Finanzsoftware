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


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentOrderConfirmationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('check_1', CheckboxType::class, [
            'label' => 'payment_order.confirm.check1.label'
        ]);
        $builder->add('check_2', CheckboxType::class, [
            'label' => 'payment_order.confirm.check2.label'
        ]);
        $builder->add('check_3', CheckboxType::class, [
            'label' => 'payment_order.confirm.check3.label'
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'payment_order.confirm.submit.label'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', null);
    }
}