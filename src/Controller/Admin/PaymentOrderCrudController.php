<?php

namespace App\Controller\Admin;

use App\Entity\PaymentOrder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PaymentOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PaymentOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('payment_order.label')
            ->setEntityLabelInPlural('payment_order.labelp')
            ->setSearchFields(['id', 'first_name', 'last_name', 'project_name', 'amount', 'comment', 'bank_info.account_owner', 'bank_info.street', 'bank_info.zip_code', 'bank_info.city', 'bank_info.iban', 'bank_info.bic', 'bank_info.bank_name', 'bank_info.reference']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        $panel1 = FormField::addPanel('payment_order.group.info');
        $firstName = TextField::new('first_name', 'payment_order.first_name.label');
        $lastName = TextField::new('last_name', 'payment_order.last_name.label');
        $department = AssociationField::new('department', 'payment_order.department.label')->autocomplete();
        $amount = MoneyField::new('amount', 'payment_order.amount.label')->setCurrency('EUR')->setStoredAsCents(true);
        $projectName = TextField::new('project_name', 'payment_order.project_name.label');
        $comment = TextEditorField::new('comment', 'payment_order.comment.label')->setRequired(false);
        $panel2 = FormField::addPanel('payment_order.group.status');
        $mathematicallyCorrect = Field::new('mathematically_correct', 'payment_order.mathematically_correct.label')->setHelp('payment_order.mathematically_correct.help');
        $factuallyCorrect = Field::new('factually_correct', 'payment_order.factually_correct.label')->setHelp('payment_order.factually_correct.help');
        $panel3 = FormField::addPanel('payment_order.group.receiver');
        $bankInfoAccountOwner = TextField::new('bank_info.account_owner', 'bank_info.account_owner.label');
        $bankInfoStreet = TextField::new('bank_info.street', 'bank_info.street.label');
        $bankInfoZipCode = TextField::new('bank_info.zip_code', 'bank_info.zip_code.label');
        $bankInfoCity = TextField::new('bank_info.city', 'bank_info.city.label');
        $panel4 = FormField::addPanel('payment_order.group.bank_info');
        $bankInfoIban = TextField::new('bank_info.iban', 'bank_info.iban.label');
        $bankInfoBic = TextField::new('bank_info.bic', 'bank_info.bic.label');
        $bankInfoBankName = TextField::new('bank_info.bank_name', 'bank_info.bank_name.label');
        $bankInfoReference = TextField::new('bank_info.reference', 'bank_info.reference.label')->setRequired(false);
        $id = IntegerField::new('id', 'payment_order.id.label');
        $lastModified = DateTimeField::new('last_modified', 'last_modified');
        $creationDate = DateTimeField::new('creation_date', 'creation_date');
        $departmentName = TextareaField::new('department.name', 'payment_order.department.label');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $projectName, $departmentName, $amount, $mathematicallyCorrect, $factuallyCorrect, $creationDate];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $firstName, $lastName, $projectName, $amount, $mathematicallyCorrect, $factuallyCorrect, $comment, $bankInfoAccountOwner, $bankInfoStreet, $bankInfoZipCode, $bankInfoCity, $bankInfoIban, $bankInfoBic, $bankInfoBankName, $bankInfoReference, $department, $lastModified, $creationDate];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$panel1, $firstName, $lastName, $department, $amount, $projectName, $comment, $panel2, $mathematicallyCorrect, $factuallyCorrect, $panel3, $bankInfoAccountOwner, $bankInfoStreet, $bankInfoZipCode, $bankInfoCity, $panel4, $bankInfoIban, $bankInfoBic, $bankInfoBankName, $bankInfoReference];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$panel1, $firstName, $lastName, $department, $amount, $projectName, $comment, $panel2, $mathematicallyCorrect, $factuallyCorrect, $panel3, $bankInfoAccountOwner, $bankInfoStreet, $bankInfoZipCode, $bankInfoCity, $panel4, $bankInfoIban, $bankInfoBic, $bankInfoBankName, $bankInfoReference];
        }
    }
}
