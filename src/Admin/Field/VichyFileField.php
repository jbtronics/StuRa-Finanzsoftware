<?php


namespace App\Admin\Field;


use App\Entity\PaymentOrder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Vich\UploaderBundle\Form\Type\VichFileType;

class VichyFileField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null)
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            //->setTemplateName('crud/field/image')
            ->setTemplatePath('admin/field/vichy_file.html.twig')
            ->setFormType(VichFileType::class)
            ->setFormTypeOption('allow_delete', false)
            ->setFormTypeOption('required', false)
            //->addCssClass('field-image')
            ->setTextAlign('center');
            //->setCustomOption(self::OPTION_BASE_PATH, null);
    }
}