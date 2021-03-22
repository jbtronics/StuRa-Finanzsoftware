<?php

namespace App\DataFixtures;

use App\Entity\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class DepartmentFixture extends Fixture
{
    public const DEPARTMENT1_REFERENCE = 'department1';
    public const DEPARTMENT2_REFERENCE = 'department2';
    public const DEPARTMENT3_REFERENCE = 'department3';
    public const DEPARTMENT4_REFERENCE = 'department4';
    public const DEPARTMENT5_REFERENCE = 'department5';

    protected $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function load(ObjectManager $manager)
    {
        //Reset autoincrement
        $this->em->getConnection()
            ->exec('ALTER TABLE `departments` AUTO_INCREMENT = 1;');
        //ALTER TABLE does an implicit commit and PHP 8 throws if commit is called later internally without active transactions
        $this->em->getConnection()->beginTransaction();

        $department = new Department();
        $department->setName('Department 1');
        $department->setType('fsr');
        $this->addReference(self::DEPARTMENT1_REFERENCE, $department);
        $manager->persist($department);

        $department = new Department();
        $department->setName('Department 2');
        $department->setType('misc');
        $department->setBlocked(true);
        $department->setSkipBlockedValidationTokens(['token1', 'token2']);
        $department->setContactEmails(['test@invalid.com', 'test@invalid.de']);
        $department->setEmailHhv(['hhv@invalid.com']);
        $department->setEmailTreasurer(['treasurer@invalid.com', 'treasurer2@invalid.com']);
        $this->addReference(self::DEPARTMENT2_REFERENCE, $department);
        $manager->persist($department);

        $department = new Department();
        $department->setName('Department 3');
        $department->setType('fsr');
        $department->setBankAccount($this->getReference(BankAccountFixture::BANK_ACCOUNT1_REFERENCE));
        $department->setComment('Test');
        $department->setContactEmails(['test@invalid.com', 'test@invalid.de']);
        $department->setEmailHhv(['hhv@invalid.com']);
        $department->setEmailTreasurer(['treasurer@invalid.com', 'treasurer2@invalid.com']);
        $this->addReference(self::DEPARTMENT3_REFERENCE, $department);
        $manager->persist($department);

        $department = new Department();
        $department->setName('Department 4');
        $department->setType('fsr');
        $department->setBankAccount($this->getReference(BankAccountFixture::BANK_ACCOUNT2_REFERENCE));
        $department->setContactEmails(['test@invalid.com']);
        $this->addReference(self::DEPARTMENT4_REFERENCE, $department);
        $manager->persist($department);

        $department = new Department();
        $department->setName('Department 5');
        $department->setType('section');
        $department->setBankAccount($this->getReference(BankAccountFixture::BANK_ACCOUNT3_REFERENCE));
        $department->setEmailHhv(['hhv@invalid.com']);
        $this->addReference(self::DEPARTMENT5_REFERENCE, $department);
        $manager->persist($department);

        $manager->flush();
    }
}
