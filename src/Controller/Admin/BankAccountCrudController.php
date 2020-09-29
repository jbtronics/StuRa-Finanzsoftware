<?php

namespace App\Controller\Admin;

use App\Entity\BankAccount;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

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
            ->setEntityLabelInPlural('bank_account.labelp');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'bank_account.id.label')->hideOnForm(),
            TextField::new('name', 'bank_account.name.label'),
            TextField::new('iban', 'bank_account.iban.label'),
            TextField::new('bic', 'bank_account.bic.label'),
            TextField::new('account_name', 'bank_account.account_name.label')
                ->setRequired(false)->setFormTypeOption('empty_data', '')
                ->setHelp('bank_account.account_name.help'),

            TextEditorField::new('comment', 'bank_account.comment.label')
                ->setRequired(false)->setFormTypeOption('empty_data', '')
                ->hideOnIndex(),
        ];
    }
}
