<?php

namespace App\DataFixtures;

use App\Entity\BankAccount;
use App\Entity\Confirmer;
use App\Entity\Department;
use App\Entity\DepartmentTypes;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

final class DepartmentFixture extends Fixture implements DependentFixtureInterface
{
    public const DEPARTMENT1_REFERENCE = 'department1';
    public const DEPARTMENT2_REFERENCE = 'department2';
    public const DEPARTMENT3_REFERENCE = 'department3';
    public const DEPARTMENT4_REFERENCE = 'department4';
    public const DEPARTMENT5_REFERENCE = 'department5';

    public function __construct()
    {
    }

    public function load(ObjectManager $manager): void
    {
        $department = new Department();
        $department->setName('Department 1');
        $department->setType(DepartmentTypes::FSR);
        $this->addReference(self::DEPARTMENT1_REFERENCE, $department);
        $manager->persist($department);

        $department = new Department();
        $department->setName('Department 2');
        $department->setType(DepartmentTypes::ADMINISTRATIVE);
        $department->setBlocked(true);
        $department->setSkipBlockedValidationTokens(['token1', 'token2']);
        $department->setContactEmails(['test@invalid.com', 'test@invalid.de']);
        $department->getConfirmers()->add($this->getReference(ConfirmerFixture::HHV_1, Confirmer::class));
        $department->getConfirmers()->add($this->getReference(ConfirmerFixture::TREASURER_1, Confirmer::class));
        $department->getConfirmers()->add($this->getReference(ConfirmerFixture::TREASURER_2, Confirmer::class));
        $this->addReference(self::DEPARTMENT2_REFERENCE, $department);
        $manager->persist($department);

        $department = new Department();
        $department->setName('Department 3');
        $department->setType(DepartmentTypes::FSR);
        $department->setBankAccount($this->getReference(BankAccountFixture::BANK_ACCOUNT1_REFERENCE, BankAccount::class));
        $department->setComment('Test');
        $department->setContactEmails(['test@invalid.com', 'test@invalid.de']);
        $department->getConfirmers()->add($this->getReference(ConfirmerFixture::HHV_1, Confirmer::class));
        $department->getConfirmers()->add($this->getReference(ConfirmerFixture::TREASURER_1, Confirmer::class));
        $department->getConfirmers()->add($this->getReference(ConfirmerFixture::TREASURER_2, Confirmer::class));
        $this->addReference(self::DEPARTMENT3_REFERENCE, $department);
        $manager->persist($department);

        $department = new Department();
        $department->setName('Department 4');
        $department->setType(DepartmentTypes::FSR);
        $department->setBankAccount($this->getReference(BankAccountFixture::BANK_ACCOUNT2_REFERENCE, BankAccount::class));
        $department->setContactEmails(['test@invalid.com']);
        $this->addReference(self::DEPARTMENT4_REFERENCE, $department);
        $manager->persist($department);

        $department = new Department();
        $department->setName('Department 5');
        $department->setType(DepartmentTypes::SECTION);
        $department->setBankAccount($this->getReference(BankAccountFixture::BANK_ACCOUNT3_REFERENCE, BankAccount::class));
        $department->getConfirmers()->add($this->getReference(ConfirmerFixture::HHV_1, Confirmer::class));
        $this->addReference(self::DEPARTMENT5_REFERENCE, $department);
        $manager->persist($department);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ConfirmerFixture::class
        ];
    }
}
