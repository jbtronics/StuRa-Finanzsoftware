<?php


namespace App\Admin\Field;


use Doctrine\DBAL\Types\TextType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

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