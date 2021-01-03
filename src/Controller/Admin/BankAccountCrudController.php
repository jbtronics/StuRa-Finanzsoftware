<?php

namespace App\Controller\Admin;

use App\Entity\BankAccount;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BankAccountCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BankAccount::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('bank_account.label')
            ->setSearchFields(['name', 'iban', 'bic', 'comment', 'account_name'])
            ->setEntityLabelInPlural('bank_account.labelp');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->setPermissions([
            Action::EDIT => 'ROLE_EDIT_BANK_ACCOUNTS',
            Action::DELETE => 'ROLE_EDIT_BANK_ACCOUNTS',
            Action::NEW => 'ROLE_EDIT_BANK_ACCOUNTS',
            Action::INDEX => 'ROLE_READ_BANK_ACCOUNTS',
            Action::DETAIL => 'ROLE_READ_BANK_ACCOUNTS',
        ]);

        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'bank_account.id.label')->hideOnForm(),
            TextField::new('name', 'bank_account.name.label'),
            TextField::new('iban', 'bank_account.iban.label'),
            TextField::new('bic', 'bank_account.bic.label'),
            TextField::new('account_name', 'bank_account.account_name.label')
                ->setRequired(false)
                ->setFormTypeOption('empty_data', '')
                ->setHelp('bank_account.account_name.help'),

            DateTimeField::new('last_modified', 'last_modified')->onlyOnDetail(),
            DateTimeField::new('creation_date', 'creation_date')->onlyOnDetail(),

            TextEditorField::new('comment', 'bank_account.comment.label')
                ->setRequired(false)
                ->setFormTypeOption('empty_data', '')
                ->hideOnIndex(),
        ];
    }
}
