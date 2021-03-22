<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    public const USER_ADMIN_REFERENCE = 'user_admin';
    public const USER_HHV_REFERENCE = 'user_hhv';
    public const USER_EXPORTER_REFERENCE = 'user_exporter';
    public const USER_READONLY_REFERENCE = 'user_readonly';

    protected $em;
    protected $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->em = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        //Reset autoincrement
        $this->em->getConnection()
            ->exec('ALTER TABLE `user` AUTO_INCREMENT = 1;');
        //ALTER TABLE does an implicit commit and PHP 8 throws if commit is called later internally without active transactions
        $this->em->getConnection()->beginTransaction();

        $user = new User();
        $user->setUsername('admin');
        //We use plaintext encoder so we can just set the PW here
        $user->setPassword($this->passwordEncoder->encodePassword($user, '1234'));
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setRoles(['ROLE_ADMIN',
            'ROLE_EDIT_USER',
            'ROLE_EDIT_ORGANISATIONS',
            'ROLE_SHOW_PAYMENT_ORDERS',
            'ROLE_EDIT_PAYMENT_ORDERS',
            'ROLE_PO_FACTUALLY',
            'ROLE_PO_MATHEMATICALLY',
            'ROLE_EDIT_BANK_ACCOUNTS',
            'ROLE_VIEW_AUDITS',
            'ROLE_MANUAL_CONFIRMATION',
        ]);
        $this->addReference(self::USER_ADMIN_REFERENCE, $user);
        $manager->persist($user);

        $user = new User();
        $user->setUsername('hhv');
        //We use plaintext encoder so we can just set the PW here
        $user->setPassword($this->passwordEncoder->encodePassword($user, '1234'));
        $user->setRoles(['ROLE_ADMIN',
            'ROLE_EDIT_ORGANISATIONS',
            'ROLE_SHOW_PAYMENT_ORDERS',
            'ROLE_EDIT_PAYMENT_ORDERS',
            'ROLE_PO_FACTUALLY',
            'ROLE_EDIT_BANK_ACCOUNTS',
            'ROLE_VIEW_AUDITS',
        ]);
        $this->addReference(self::USER_HHV_REFERENCE, $user);
        $manager->persist($user);

        $user = new User();
        $user->setUsername('exporter');
        //We use plaintext encoder so we can just set the PW here
        $user->setPassword($this->passwordEncoder->encodePassword($user, '1234'));
        $user->setRoles(['ROLE_ADMIN',
            'ROLE_SHOW_PAYMENT_ORDERS',
            'ROLE_EDIT_PAYMENT_ORDERS',
            'ROLE_PO_MATHEMATICALLY',
        ]);
        $this->addReference(self::USER_EXPORTER_REFERENCE, $user);
        $manager->persist($user);

        $user = new User();
        $user->setUsername('readonly');
        //We use plaintext encoder so we can just set the PW here
        $user->setPassword($this->passwordEncoder->encodePassword($user, '1234'));
        $user->setRoles(['ROLE_ADMIN',
            'ROLE_SHOW_PAYMENT_ORDERS',
        ]);
        $this->addReference(self::USER_READONLY_REFERENCE, $user);
        $manager->persist($user);

        $manager->flush();
    }
}
