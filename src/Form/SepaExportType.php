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

use App\Entity\BankAccount;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Bic;
use Symfony\Component\Validator\Constraints\Iban;

class SepaExportType extends AbstractType
{
    private const MODE_CHOICES = [
        'sepa_export.mode.auto' => 'auto',
        'sepa_export.mode.manual' => 'manual',
        'sepa_export.mode.auto_single' => 'auto_single',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('mode', ChoiceType::class, [
            'expanded' => true,
            'data' => 'auto_single',
            'label' => 'sepa_export.mode.label',
            'choices' => self::MODE_CHOICES,
        ]);

        $builder->add('bank_account', EntityType::class, [
            'label' => 'sepa_export.bank_account.label',
            'required' => false,
            'class' => BankAccount::class,
            'attr' => [
                'class' => 'field-association select2',
                //Define the handler to enable/disable the other fields (this is a bit hacky though)...
                'onchange' => 'onPresetChange(this);',
                'data-mode-manual' => true,
            ],
            'placeholder' => 'sepa_export.bank_account.placeholder',
            'choice_label' => fn(BankAccount $account): string => $account->getExportAccountName().' ['.$account->getIban().']',
        ]);

        $builder->add('name', TextType::class, [
            'label' => 'sepa_export.name.label',
            'attr' => [
                'data-manual-input' => true,
                'data-mode-manual' => true,
            ],
        ]);
        $builder->add('iban', TextType::class, [
            'label' => 'sepa_export.iban.label',
            'constraints' => [new Iban()],
            'attr' => [
                'data-manual-input' => true,
                'data-mode-manual' => true,
            ],
        ]);
        $builder->add('bic', TextType::class, [
            'label' => 'sepa_export.bic.label',
            'constraints' => [new Bic()],
            'attr' => [
                'data-manual-input' => true,
                'data-mode-manual' => true,
            ],
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'sepa_export.submit',
        ]);
    }
}
