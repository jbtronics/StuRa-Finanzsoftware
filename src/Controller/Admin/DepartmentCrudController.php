<?php

namespace App\Controller\Admin;

use App\Entity\Department;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DepartmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Department::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('department.label')
            ->setEntityLabelInPlural('department.labelp')
            ->setSearchFields(['id', 'name', 'type', 'comment']);
    }

    public function configureFields(string $pageName): iterable
    {
        $choices = [];
        foreach(Department::ALLOWED_TYPES as $type) {
            $choices['department.type.' . $type ] = $type;
        }

        $name = TextField::new('name', 'department.name.label');
        $type = ChoiceField::new('type', 'department.type.label')->setChoices($choices)->autocomplete();
        $blocked = BooleanField::new('blocked', 'department.blocked.label')->renderAsSwitch(true)->setHelp('department.blocked.help');
        $comment = TextEditorField::new('comment', 'department.comment.label')->setRequired(false)->setFormTypeOption('empty_data', '');
        $id = IntegerField::new('id', 'department.id.label');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $type, $blocked];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $type, $blocked, $comment];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$name, $type, $blocked, $comment];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$name, $type, $blocked, $comment];
        }
    }
}
