<?php
/*
 * Copyright (C) 2020  Jan Böhmer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Controller\Admin;

use App\Entity\Department;
use App\Entity\PaymentOrder;
use App\Services\EmailConfirmation\ConfirmationTokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use LogicException;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Response;

class DepartmentCrudController extends AbstractCrudController
{
    private $tokenGenerator;
    private $entityManager;

    public function __construct(ConfirmationTokenGenerator $tokenGenerator, EntityManagerInterface $entityManager)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->entityManager = $entityManager;
    }

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

    public function generateSkipToken(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EDIT_ORGANISATIONS');

        /** @var Department $department */
        $department = $context->getEntity()->getInstance();

        $department->addSkipBlockedValidationToken($this->tokenGenerator->getToken());
        $this->entityManager->flush();

        return $this->redirect($context->getReferrer() ?? '/admin');
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

        $generateTokenAction = Action::new('generateSkipToken', 'department.action.generate_skip_token', 'fas fa-award')
            ->displayIf(function (Department $paymentOrder) {
                return $this->isGranted('ROLE_EDIT_ORGANISATIONS');
            })
            ->setCssClass('mr-2 text-dark')
            ->linkToCrudAction('generateSkipToken');

        $actions->add(Crud::PAGE_DETAIL, $generateTokenAction);
        $actions->add(Crud::PAGE_EDIT, $generateTokenAction);

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
        foreach (Department::ALLOWED_TYPES as $type) {
            $choices['department.type.'.$type] = $type;
        }

        return [
            //Basic informations
            TextField::new('name', 'department.name.label'),
            ChoiceField::new('type', 'department.type.label')
                ->setChoices($choices)
                ->autocomplete(),
            BooleanField::new('blocked', 'department.blocked.label')
                ->renderAsSwitch(true)
                ->setHelp('department.blocked.help'),
            TextEditorField::new('comment', 'department.comment.label')
                ->setRequired(false)
                ->setFormTypeOption('empty_data', '')
                ->hideOnIndex(),
            IntegerField::new('id', 'department.id.label')
                ->onlyOnDetail(),
            DateTimeField::new('last_modified', 'last_modified')
                ->onlyOnDetail(),
            DateTimeField::new('creation_date', 'creation_date')
                ->onlyOnDetail(),

            AssociationField::new('bank_account', 'department.bank_account.label')
                ->setHelp('department.bank_account.help')
                ->setRequired(false)
                ->hideOnDetail(),

            CollectionField::new('contact_emails', 'department.contact_emails.label')
                ->setHelp('department.contact_emails.help')
                ->setTemplatePath('admin/field/email_collection.html.twig')
                ->allowAdd()
                ->allowDelete()
                ->setFormTypeOption('delete_empty', true)
                ->setFormTypeOption('entry_options.required', false)
                ->setTemplatePath('admin/field/email_collection.html.twig')
                ->setEntryType(EmailType::class),

            TextField::new('references_export_prefix', 'department.references_export_prefix.label')
                ->setHelp('department.references_export_prefix.help')
                ->hideOnIndex(),

            //FSR contact info panel
            FormField::addPanel('department.fsr_email_panel.label')
                ->setHelp('department.fsr_email_panel.help'),
            CollectionField::new('email_hhv', 'department.email_hhv.label')
                ->setHelp('department.email_hhv.help')
                ->setTemplatePath('admin/field/email_collection.html.twig')
                ->allowAdd()
                ->allowDelete()
                ->setFormTypeOption('delete_empty', true)
                ->setFormTypeOption('entry_options.required', false)
                ->setFormTypeOption('entry_options.empty_data', '')
                ->setEntryType(EmailType::class)
                ->hideOnIndex(),

            CollectionField::new('email_treasurer', 'department.email_treasurer.label')
                ->setHelp('department.email_hhv.help')
                ->setTemplatePath('admin/field/email_collection.html.twig')
                ->allowAdd()
                ->allowDelete()
                ->setFormTypeOption('delete_empty', true)
                ->setFormTypeOption('entry_options.required', false)
                ->setFormTypeOption('entry_options.empty_data', '')
                ->setEntryType(EmailType::class)
                ->hideOnIndex(),

            FormField::addPanel('department.skip_blocked_validation_tokens.panel.label')
                ->setHelp('department.skip_blocked_validation_tokens.panel.help'),

            CollectionField::new('skip_blocked_validation_tokens', 'department.skip_blocked_validation_tokens.label')
                ->allowDelete()
                ->allowAdd(false)
                ->setFormTypeOption('delete_empty', false)
                ->setTemplatePath('admin/field/validation_token_collection.html.twig')
                ->hideOnIndex(),


        ];
    }
}
