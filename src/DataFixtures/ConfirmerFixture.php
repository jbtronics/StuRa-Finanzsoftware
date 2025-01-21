<?php

declare(strict_types=1);


namespace App\DataFixtures;

use App\Entity\Confirmer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ConfirmerFixture extends Fixture
{
    public const HHV_1 = 'hhv@invalid.com';
    public const TREASURER_1 = 'treasurer@invalid.com';
    public const TREASURER_2 = 'treasurer2@invalid.com';

    public function load(ObjectManager $manager): void
    {
        $confirmer = new Confirmer();
        $confirmer->setEmail('hhv@invalid.com');
        $confirmer->setName('HHV 1');
        $this->addReference(self::HHV_1, $confirmer);
        $manager->persist($confirmer);

        $confirmer = new Confirmer();
        $confirmer->setEmail('treasurer@invalid.com');
        $confirmer->setName('Treasurer 1');
        $this->addReference(self::TREASURER_1, $confirmer);
        $manager->persist($confirmer);

        $confirmer = new Confirmer();
        $confirmer->setEmail('treasurer2@invalid.com');
        $confirmer->setName('Treasurer 2');
        $this->addReference(self::TREASURER_2, $confirmer);
        $manager->persist($confirmer);

        $manager->flush();
    }
}