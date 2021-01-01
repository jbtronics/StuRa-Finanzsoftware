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

namespace App\Form\TFA;

use App\Entity\User;
use App\Validator\ValidGoogleAuthCode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TFAGoogleSettingsType extends AbstractType
{
    protected $translator;

    public function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $form = $event->getForm();
            /** @var User $user */
            $user = $event->getData();

            //Only show setup fields, when google authenticator is not enabled
            if (!$user->isGoogleAuthenticatorEnabled()) {
                $form->add(
                    'google_confirmation',
                    TextType::class,
                    [
                        'mapped' => false,
                        'label' => 'tfa_google.code',
                        'attr' => [
                            'maxlength' => '6',
                            'minlength' => '6',
                            'pattern' => '\d*',
                            'autocomplete' => 'off',
                        ],
                        'constraints' => [new ValidGoogleAuthCode()],
                    ]
                );

                $form->add(
                    'googleAuthenticatorSecret',
                    HiddenType::class,
                    [
                        'disabled' => false,
                    ]
                );

                $form->add('submit', SubmitType::class, [
                    'label' => 'tfa_google.enable',
                    'attr' => [
                        'class' => 'btn-danger offset-sm-3',
                    ],
                ]);
            } else {
                $form->add('submit', SubmitType::class, [
                    'label' => 'tfa_google.disable',
                    'attr' => [
                        'class' => 'btn-danger offset-sm-3',
                    ],
                ]);
            }
        });

        //$builder->add('cancel', ResetType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
                                   'data_class' => User::class,
                               ]);
    }
}
