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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PaymentOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('submitter_name', TextType::class, [
            'label' => 'payment_order.submitter_name.label',
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'payment_order.submitter_name.placeholder',
                'autocomplete' => 'given_name',
            ],
        ]);

        $builder->add('submitter_email', EmailType::class, [
            'label' => 'payment_order.contact_email.label',
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'payment_order.contact_email.placeholder',
                'autocomplete' => 'email',
            ],
        ]);

        $builder->add('department', DepartmentChoiceType::class, [
            'label' => 'payment_order.department.label',
        ]);

        $builder->add('funding_id', TextType::class, [
            'label' => 'payment_order.funding_id.label',
            'attr' => [
                'placeholder' => 'payment_order.funding_id.placeholder',
            ],
        ]);

        $builder->add('amount', MoneyType::class, [
            'label' => 'payment_order.amount.label',
            'divisor' => 100,
            'currency' => 'EUR',
            'attr' => [
                'placeholder' => 'payment_order.amount.placeholder',
            ],
        ]);

        $builder->add('supporting_amount', MoneyType::class, [
            'label' => 'payment_order.supporting_amount.label',
            'divisor' => 100,
            'currency' => 'EUR',
            'required' => false,
            'attr' => [
                'placeholder' => 'payment_order.amount.placeholder',
            ],
        ]);

        $builder->add('supporting_funding_id', TextType::class, [
            'label' => 'payment_order.supporting_funding_id.label',
            'required' => false,
            'attr' => [
                'placeholder' => 'payment_order.supporting_funding_id.placeholder',
            ],
        ]);


        $builder->add('resolution_date', DateType::class, [
            'label' => 'payment_order.resolution_date.label',
            'required' => false,
            'html5' => true,
            'widget' => 'single_text',
        ]);

        $builder->add('project_name', TextType::class, [
            'label' => 'payment_order.project_name.label',
            'attr' => [
                'placeholder' => 'payment_order.project_name.placeholder',
            ],
        ]);


        $builder->add('invoice_number', TextType::class, [
            'label' => 'payment_order.invoice_number.label',
            'required' => false,
        ]);
        $builder->add('customer_number', TextType::class, [
            'label' => 'payment_order.customer_number.label',
            'required' => false
        ]);

        $builder->add('bank_info', PayeeInfoType::class, [
            'label' => false,
        ]);

        /*
        $builder->add('printed_form_file', VichFileType::class, [
            'label' => 'payment_order.printed_form.label',
            'help' => 'payment_order.printed_form.help',
            'help_html' => true,
        ]); */

        $builder->add('references_file', VichFileType::class, [
            'label' => 'payment_order.references.label',
        ]);

        $builder->add('comment', TextareaType::class, [
            'label' => 'payment_order.comment.label',
            'empty_data' => '',
            'required' => false,
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', PaymentOrder::class);
        $resolver->setDefault('validation_groups', ['Default', 'frontend', 'fsr_blocked']);
    }
}
