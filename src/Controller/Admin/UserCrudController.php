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

use App\Admin\Field\PasswordField;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserCrudController extends AbstractCrudController
{
    private $encoder;

    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->setPermissions([
            Action::EDIT => 'ROLE_EDIT_USER',
            Action::DELETE => 'ROLE_EDIT_USER',
            Action::NEW => 'ROLE_EDIT_USER',
            Action::INDEX => 'ROLE_READ_USER',
            Action::DETAIL => 'ROLE_READ_USER',
        ]);

        return parent::configureActions($actions);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('user.label')
            ->setEntityLabelInPlural('user.labelp')
            ->setFormOptions([
                'validation_groups' => ['Default', 'perm_edit'],
            ])
            ->setSearchFields(['id', 'username', 'role_description', 'email', 'roles', 'first_name', 'last_name']);
    }

    private function getRoleChoices(): array
    {
        //If something changes here, change it in templates/user/_user_info.html.twig too
        return [
            'user.role.access_admin' => 'ROLE_ADMIN',
            'user.role.edit_user' => 'ROLE_EDIT_USER',
            'user.role.edit_organisations' => 'ROLE_EDIT_ORGANISATIONS',
            'user.role.show_payment_orders' => 'ROLE_SHOW_PAYMENT_ORDERS',
            'user.role.edit_payment_orders' => 'ROLE_EDIT_PAYMENT_ORDERS',
            'user.role.edit_po_factually' => 'ROLE_PO_FACTUALLY',
            'user.role.edit_po_mathematically' => 'ROLE_PO_MATHEMATICALLY',
            'user.role.edit_bank_accounts' => 'ROLE_EDIT_BANK_ACCOUNTS',
            'user.role.view_audit_logs' => 'ROLE_VIEW_AUDITS',
            'user.role.export_references' => 'ROLE_EXPORT_REFERENCES',
            'user.role.manual_confirmation' => 'ROLE_MANUAL_CONFIRMATION',
        ];
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            //Basic info
            IntegerField::new('id', 'user.id.label')
                ->hideOnForm(),
            TextField::new('username', 'user.username.label'),
            TextField::new('fullName', 'user.fullName.label')
                ->onlyOnIndex(),
            TextField::new('first_name', 'user.first_name.label')
                ->setRequired(false)
                ->setFormTypeOption('empty_data', '')
                ->hideOnIndex(),
            TextField::new('last_name', 'user.last_name.label')
                ->setRequired(false)
                ->setFormTypeOption('empty_data', '')
                ->hideOnIndex(),
            EmailField::new('email', 'user.email.label')
                ->setRequired(false)
                ->setFormTypeOption('empty_data', ''),
            TextField::new('role_description', 'user.role_description.label')
                ->setRequired(false)
                ->setFormTypeOption('empty_data', ''),
            ChoiceField::new('roles', 'user.roles.label')
                ->allowMultipleChoices()
                ->setChoices($this->getRoleChoices())
                ->renderExpanded()
                ->renderAsNativeWidget()
                ->hideOnIndex(),

            //Passowrd panel
            FormField::addPanel('user.section.password')
                ->setHelp('user.section.password.help')
                ->onlyOnForms(),
            PasswordField::new('plain_password')
                ->setRequired(Crud::PAGE_NEW === $pageName)
                ->onlyOnForms(),

            //2FA panel
            FormField::addPanel('user.section.tfa')->setHelp('user.section.tfa.help'),
            BooleanField::new('tfa_enabled', 'user.tfa_enabled.label')
                ->setHelp('user.tfa_enabled.help')
                ->renderAsSwitch(false)
                ->setFormTypeOption('disabled', true),
        ];
    }

    private function setUserPlainPassword(User $user): void
    {
        if ($user->getPlainPassword()) {
            $user->setPassword($this->encoder->hashPassword($user, $user->getPlainPassword()));
            $user->setPlainPassword(null);
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->setUserPlainPassword($entityInstance);
        //Set password before persisting
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->setUserPlainPassword($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }
}
