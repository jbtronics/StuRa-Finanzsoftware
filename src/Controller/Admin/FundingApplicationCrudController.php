<?php

namespace App\Controller\Admin;

use App\Admin\Field\FundingOrganisationField;
use App\Admin\Field\VichyFileField;
use App\Entity\FundingApplication;
use App\Form\AddressType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use RuntimeException;

class FundingApplicationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FundingApplication::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->disable(Crud::PAGE_NEW);
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);

        return $actions;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('funding_application.label')
            ->setEntityLabelInPlural('funding_application.labelp');
    }

    public function configureFields(string $pageName): iterable
    {
        $funding_id = TextField::new('funding_id', 'funding_application.funding_id.label');
        $creationDate = DateTimeField::new('creation_date', 'creation_date');
        $lastModified = DateTimeField::new('last_modified', 'last_modified');

        //Application panel
        $applicant_name = TextField::new('applicant_name', 'funding_application.applicant_name.label');

        $applicant_department = FundingOrganisationField::new('applicant_department', 'funding_application.department.label')
            ->setFormTypeOption('attr', [
                'data-widget' => 'select2',
            ])
        ->setRequired(false);

        $applicant_organisation_name = TextField::new('applicant_organisation_name', 'funding_application.applicant_organisation_name.label');

        $applicant_email = EmailField::new('applicant_email', 'funding_application.applicant_email.label');
        $applicant_phone = TelephoneField::new('applicant_phone', 'funding_application.applicant_phone.label');
        $applicant_address = TextField::new('applicant_address', 'funding_application.applicant_address.label')
            ->setFormType(AddressType::class);

        $requested_amount = MoneyField::new('requested_amount', 'funding_application.requested_amount.short_label')
            ->setCurrency('EUR')
            ->setTextAlign('left')
            ->setStoredAsCents(true);

        $funding_intention = TextareaField::new('funding_intention', 'funding_application.funding_intention.label');

        $printed_form = VichyFileField::new('explanation_file', 'funding_application.explanation.label');
        $references = VichyFileField::new('finance_plan_file', 'funding_application.finance_plan.label');


        if (Crud::PAGE_INDEX === $pageName) {
            return [$funding_id, $applicant_department, $requested_amount, $creationDate];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                FormField::addPanel('funding_application.panel.application'),
                $funding_id,
                $applicant_name,
                $applicant_department,
                $applicant_email,
                $applicant_phone,
                $applicant_address,
                $requested_amount,
                $funding_intention,
                $printed_form,
                $references
            ];
        }

        if (Crud::PAGE_EDIT === $pageName) {
            return [
                FormField::addPanel('funding_application.panel.application')
                ->collapsible()->renderCollapsed(),
                $applicant_name,
                $applicant_department,
                $applicant_organisation_name,
                $applicant_email,
                $applicant_phone,
                $applicant_address,
                $requested_amount,
                $funding_intention,
                $printed_form,
                $references
            ];
        }

        throw new RuntimeException('It should not be possible to reach this point...');
    }
}
