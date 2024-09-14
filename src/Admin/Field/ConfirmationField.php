<?php

declare(strict_types=1);


namespace App\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ConfirmationField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): ConfirmationField
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('admin/field/confirmation.html.twig')
            ->setTextAlign('center');
    }
}