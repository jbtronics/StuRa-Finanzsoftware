<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public const USER_ADMIN_REFERENCE = "user_admin";
    public const USER_HHV_REFERENCE = "user_hhv";
    public const USER_EXPORTER_REFERENCE = "user_exporter";
    public const USER_READONLY_REFERENCE = "user_readonly";

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername("admin");
        //We use plaintext encoder so we can just set the PW here
        $user->setPassword("1234");
        $user->setFirstName("Admin");
        $user->setLastName("User");
        $user->setRoles(["ROLE_ADMIN",
                            "ROLE_EDIT_USER",
                            "ROLE_EDIT_ORGANISATIONS",
                            "ROLE_SHOW_PAYMENT_ORDERS",
                            "ROLE_EDIT_PAYMENT_ORDERS",
                            "ROLE_PO_FACTUALLY",
                            "ROLE_PO_MATHEMATICALLY",
                            "ROLE_EDIT_BANK_ACCOUNTS"
                        ]);
        $this->addReference(self::USER_ADMIN_REFERENCE, $user);
        $manager->persist($user);

        $user = new User();
        $user->setUsername("hhv");
        //We use plaintext encoder so we can just set the PW here
        $user->setPassword("1234");
        $user->setRoles(["ROLE_ADMIN",
                            "ROLE_EDIT_ORGANISATIONS",
                            "ROLE_SHOW_PAYMENT_ORDERS",
                            "ROLE_EDIT_PAYMENT_ORDERS",
                            "ROLE_PO_FACTUALLY",
                            "ROLE_EDIT_BANK_ACCOUNTS"
                        ]);
        $this->addReference(self::USER_HHV_REFERENCE, $user);
        $manager->persist($user);

        $user = new User();
        $user->setUsername("exporter");
        //We use plaintext encoder so we can just set the PW here
        $user->setPassword("1234");
        $user->setRoles(["ROLE_ADMIN",
                            "ROLE_SHOW_PAYMENT_ORDERS",
                            "ROLE_EDIT_PAYMENT_ORDERS",
                            "ROLE_PO_MATHEMATICALLY",
                        ]);
        $this->addReference(self::USER_EXPORTER_REFERENCE, $user);
        $manager->persist($user);

        $user = new User();
        $user->setUsername("readonly");
        //We use plaintext encoder so we can just set the PW here
        $user->setPassword("1234");
        $user->setRoles(["ROLE_ADMIN",
                            "ROLE_SHOW_PAYMENT_ORDERS",
                        ]);
        $this->addReference(self::USER_READONLY_REFERENCE, $user);
        $manager->persist($user);

        $manager->flush();
    }
}