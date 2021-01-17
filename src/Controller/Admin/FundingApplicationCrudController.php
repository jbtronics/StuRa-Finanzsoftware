<?php

namespace App\Controller\Admin;

use App\Entity\FundingApplication;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FundingApplicationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FundingApplication::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('funding_id'),
            TextField::new('applicant_name'),
        ];
    }
}
