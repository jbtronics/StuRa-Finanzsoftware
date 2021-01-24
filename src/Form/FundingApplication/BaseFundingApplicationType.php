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

namespace App\Form\FundingApplication;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;

class BaseFundingApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('applicant_name', TextType::class, [
            'label' => 'funding_application.applicant_name.label'
        ]);

        $builder->add('applicant_email', EmailType::class, [
            'label' => 'funding_application.applicant_email.label'
        ]);

        $builder->add('requested_amount', MoneyType::class, [
            'label' => 'funding_application.requested_amount.label',
            'divisor' => 100,
            'currency' => 'EUR',
            'attr' => [
                'placeholder' => 'payment_order.amount.placeholder',
            ],
        ]);

        $builder->add('funding_intention', TextareaType::class, [
           'label' => 'funding_application.funding_intention.label',
            'attr' => ['rows' => 4]
        ]);

        $builder->add('explanation_file', VichFileType::class, [
            'label' => 'funding_application.explanation.label',
            'help' => 'funding_application.explanation.help',
        ]);

        $builder->add('finance_plan_file', VichFileType::class, [
            'label' => 'funding_application.finance_plan.label',
            'help' => 'funding_application.finance_plan.help',
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'payment_order.submit',
            'attr' => [
                'class' => 'btn btn-primary',
            ],
        ]);

        $builder->add('submit_new', SubmitType::class, [
            'label' => 'payment_order.submit_new',
            'attr' => [
                'class' => 'btn btn-secondary',
            ],
        ]);

        $builder->add('reset', ResetType::class, [
            'label' => 'payment_order.discard',
            'attr' => [
                'class' => 'btn btn-danger',
            ],
        ]);
    }
}