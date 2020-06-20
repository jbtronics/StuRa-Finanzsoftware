<?php

namespace App\Controller\Admin;

use App\Admin\Field\PasswordField;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
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

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('user.label')
            ->setEntityLabelInPlural('user.labelp')
            ->setSearchFields(['id', 'username', 'role_description', 'email', 'roles', 'first_name', 'last_name']);
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
        $roles = TextField::new('roles');
        $fullName = TextField::new('fullName', 'user.fullName.label');

        $password_panel = FormField::addPanel('user.section.password')->setHelp('user.section.password.help');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $username, $fullName, $roleDescription, $email];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $username, $roleDescription, $email, $roles, $firstName, $lastName];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$username, $firstName, $lastName, $email, $roleDescription, $password_panel, $plainPassword];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$username, $firstName, $lastName, $email, $roleDescription, $password_panel, $plainPassword];
        }
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
