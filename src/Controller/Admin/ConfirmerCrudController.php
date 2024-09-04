<?php

declare(strict_types=1);


namespace App\Controller\Admin;

use App\Entity\Confirmer;
use App\Entity\Department;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Translation\TranslatableMessage as TM;

class ConfirmerCrudController extends AbstractCrudController
{

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural(new TM('confirmer.labelp'))
            ->setEntityLabelInSingular(new TM('confirmer.label'))
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->setPermissions([
            Action::EDIT => 'ROLE_EDIT_ORGANISATIONS',
            Action::DELETE => 'ROLE_EDIT_ORGANISATIONS',
            Action::NEW => 'ROLE_EDIT_ORGANISATIONS',
            Action::INDEX => 'ROLE_READ_ORGANISATIONS',
            Action::DETAIL => 'ROLE_READ_ORGANISATIONS',
        ]);

        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', new TM('confirmer.name')),
            EmailField::new('email', new TM('confirmer.email')),
            TelephoneField::new('phone', new TM('confirmer.phone'))->setRequired(false),
            TextEditorField::new('comment', new TM('confirmer.comment'))
                ->setRequired(false)
                ->setEmptyData('')
                ->hideOnIndex(),
            AssociationField::new('departments', new TM('confirmer.departments'))
                ->autocomplete()

        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'confirmer.name'))
            ->add(TextFilter::new('email', 'confirmer.email'))
            ->add(BooleanFilter::new('phone', 'confirmer.phone'))
            ->add(TextFilter::new('comment', 'confirmer.comment'))
            ->add(DateTimeFilter::new('creation_date', 'creation_date'))
            ->add(DateTimeFilter::new('last_modified', 'last_modified'));
    }

    public static function getEntityFqcn(): string
    {
        return Confirmer::class;
    }
}