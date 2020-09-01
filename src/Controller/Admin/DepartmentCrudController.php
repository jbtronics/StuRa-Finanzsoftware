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
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

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
        $actions->setPermissions([
                                     Action::EDIT => 'ROLE_EDIT_ORGANISATIONS',
                                     Action::DELETE => 'ROLE_EDIT_ORGANISATIONS',
                                     Action::NEW => 'ROLE_EDIT_ORGANISATIONS'
                                 ]);

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

        $contact_emails = CollectionField::new('contact_emails', 'department.contact_emails.label')
            ->setHelp('department.contact_emails.help')
            ->setTemplatePath('admin/field/email_collection.html.twig')
            ->allowAdd()->allowDelete()
            ->setFormTypeOption('delete_empty', true)
            ->setFormTypeOption('entry_options.required', false)
            ->setEntryType(EmailType::class);


        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $type, $blocked, $contact_emails];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $type, $blocked, $comment, $contact_emails, $creationDate, $lastModified];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$name, $type, $blocked, $contact_emails, $comment];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$name, $type, $blocked, $contact_emails, $comment];
        }

        throw new \LogicException('Invalid $pageName encountered!');
    }
}
