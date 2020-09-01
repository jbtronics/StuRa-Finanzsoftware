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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserCrudController extends AbstractCrudController
{

    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
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
                                     Action::NEW => 'ROLE_EDIT_USER'
                                 ]);

        return parent::configureActions($actions); // TODO: Change the autogenerated stub
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
        return [
            'user.role.access_admin' => 'ROLE_ADMIN',
            'user.role.edit_user' => 'ROLE_EDIT_USER',
            'user.role.edit_organisations' => 'ROLE_EDIT_ORGANISATIONS',
            'user.role.show_payment_orders' => 'ROLE_SHOW_PAYMENT_ORDERS',
            'user.role.edit_payment_orders' => 'ROLE_EDIT_PAYMENT_ORDERS',
            'user.role.edit_po_factually' => 'ROLE_PO_FACTUALLY',
            'user.role.edit_po_mathematically' => 'ROLE_PO_MATHEMATICALLY',
        ];
    }

    public function configureFields(string $pageName): iterable
    {
        $username = TextField::new('username', 'user.username.label');
        $firstName = TextField::new('first_name', 'user.first_name.label')->setRequired(false)->setFormTypeOption('empty_data', '');
        $lastName = TextField::new('last_name', 'user.last_name.label')->setRequired(false)->setFormTypeOption('empty_data', '');
        $email = EmailField::new('email', 'user.email.label')->setRequired(false)->setFormTypeOption('empty_data', '');
        $roleDescription = TextField::new('role_description', 'user.role_description.label')->setRequired(false)->setFormTypeOption('empty_data', '');
        $plainPassword = PasswordField::new('plain_password')->setRequired(Crud::PAGE_NEW === $pageName);
        $id = IntegerField::new('id', 'user.id.label');
        $roles = ChoiceField::new('roles')->allowMultipleChoices()->setChoices($this->getRoleChoices())->renderExpanded()->renderAsNativeWidget()->setLabel('user.roles.label');
        $fullName = TextField::new('fullName', 'user.fullName.label');

        $password_panel = FormField::addPanel('user.section.password')->setHelp('user.section.password.help');

        $tfa_panel = FormField::addPanel('user.section.tfa')->setHelp('user.section.tfa.help');
        $tfa_enabled = BooleanField::new('tfa_enabled', 'user.tfa_enabled.label')
            ->setHelp('user.tfa_enabled.help')->renderAsSwitch(false)->setFormTypeOption('disabled', true);

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $username, $fullName, $roleDescription, $email, $tfa_enabled];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $username, $roleDescription, $email, $roles, $firstName, $lastName, $roles, $tfa_enabled];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$username, $firstName, $lastName, $email, $roleDescription, $roles, $password_panel, $plainPassword, $tfa_panel, $tfa_enabled];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$username, $firstName, $lastName, $email, $roleDescription, $roles, $password_panel, $plainPassword, $tfa_panel, $tfa_enabled];
        }

        throw new \LogicException('Invalid $pageName encountered!');
    }

    private function setUserPlainPassword(User $user): void
    {
        if ($user->getPlainPassword()) {
            $user->setPassword($this->encoder->encodePassword($user, $user->getPlainPassword()));
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
