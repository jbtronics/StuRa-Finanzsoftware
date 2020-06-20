<?php

namespace App\Controller\Admin;

use App\Admin\Filter\MoneyAmountFilter;
use App\Entity\Department;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

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

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('blocked', 'department.blocked.label'))
            ->add(DateTimeFilter::new('creation_date', 'creation_date'))
            ->add(DateTimeFilter::new('last_modified', 'last_modified'));
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
        $lastModified = DateTimeField::new('last_modified', 'last_modified');
        $creationDate = DateTimeField::new('creation_date', 'creation_date');


        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $type, $blocked];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $type, $blocked, $comment, $creationDate, $lastModified];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$name, $type, $blocked, $comment];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$name, $type, $blocked, $comment];
        }
    }
}
