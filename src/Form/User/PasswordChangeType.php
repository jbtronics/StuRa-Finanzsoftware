<?php


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
            'constraints' => [new UserPassword()]
        ]);

        $builder->add('plain_password', RepeatedType::class, [
            'label' => false,
            'type' => PasswordType::class,
            'first_options' => ['label' => 'password.new'],
            'second_options' => ['label' => 'password.repeat'],
            'constraints' => [new Length(['min' => 6])]
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'submit'
        ]);
    }
}