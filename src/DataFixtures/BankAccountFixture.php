<?php

namespace App\DataFixtures;

use App\Entity\BankAccount;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

final class BankAccountFixture extends Fixture
{
    public const BANK_ACCOUNT1_REFERENCE = 'bank_account1';
    public const BANK_ACCOUNT2_REFERENCE = 'bank_account2';
    public const BANK_ACCOUNT3_REFERENCE = 'bank_account3';

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function load(ObjectManager $manager): void
    {
        //Reset autoincrement
        $this->em->getConnection()
            ->executeStatement('ALTER TABLE `bank_accounts` AUTO_INCREMENT = 1;');
        //ALTER TABLE does an implicit commit and PHP 8 throws if commit is called later internally without active transactions
        $this->em->getConnection()->beginTransaction();

        $account = new BankAccount();
        $account->setName('Bank Account 1');
        $account->setIban('DE56500105174413384824');
        $account->setBic('INGDDEFFXXX');
        $this->addReference(self::BANK_ACCOUNT1_REFERENCE, $account);
        $manager->persist($account);

        $account = new BankAccount();
        $account->setName('Bank Account 2');
        $account->setIban('DE56500105174413384824');
        $account->setBic('INGDDEFFXXX');
        $account->setAccountName('Account Name');
        $account->setComment('Test');
        $this->addReference(self::BANK_ACCOUNT2_REFERENCE, $account);
        $manager->persist($account);

        $account = new BankAccount();
        $account->setName('Bank Account 3');
        $account->setIban('DE98500105174783924844');
        $account->setBic('INGDDEFFXXX');
        $account->setAccountName('Account Name');
        $this->addReference(self::BANK_ACCOUNT3_REFERENCE, $account);
        $manager->persist($account);

        $manager->flush();
    }
}
