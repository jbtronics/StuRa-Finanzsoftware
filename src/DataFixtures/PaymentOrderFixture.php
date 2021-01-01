<?php

namespace App\DataFixtures;

use App\Entity\PaymentOrder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PaymentOrderFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $payment_order = new PaymentOrder();
        $payment_order->setFirstName('John');
        $payment_order->setLastName('Doe');
        $payment_order->setContactEmail('test@invalid.com');
        $payment_order->setFundingId('M-123-2020');
        $payment_order->setProjectName('Test');
        $payment_order->setFsrKomResolution(true);
        $payment_order->setDepartment($this->getReference(DepartmentFixture::DEPARTMENT3_REFERENCE));
        $payment_order->setAmount(12340);
        $payment_order->setComment('Test');
        $payment_order->getBankInfo()
            ->setAccountOwner('John Doe');
        $payment_order->getBankInfo()
            ->setIban('DE98 5001 0517 4783 9248 44');
        $payment_order->getBankInfo()
            ->setStreet('Street 1');
        $payment_order->getBankInfo()
            ->setZipCode('12345');
        $payment_order->getBankInfo()
            ->setCity('Jena');
        $manager->persist($payment_order);

        $payment_order = new PaymentOrder();
        $payment_order->setFirstName('John');
        $payment_order->setLastName('Doe');
        $payment_order->setContactEmail('test@invalid.com');
        $payment_order->setFundingId('');
        $payment_order->setProjectName('Test');
        $payment_order->setFsrKomResolution(false);
        $payment_order->setDepartment($this->getReference(DepartmentFixture::DEPARTMENT2_REFERENCE));
        $payment_order->setAmount(12340);
        $payment_order->setComment('Test');
        $payment_order->getBankInfo()
            ->setAccountOwner('John Doe');
        $payment_order->getBankInfo()
            ->setIban('DE98 5001 0517 4783 9248 44');
        $payment_order->getBankInfo()
            ->setStreet('Street 1');
        $payment_order->getBankInfo()
            ->setZipCode('12345');
        $payment_order->getBankInfo()
            ->setCity('Jena');
        $manager->persist($payment_order);

        $payment_order = new PaymentOrder();
        $payment_order->setFirstName('John');
        $payment_order->setLastName('Doe');
        $payment_order->setContactEmail('test@invalid.com');
        $payment_order->setFundingId('');
        $payment_order->setProjectName('Test23');
        $payment_order->setFsrKomResolution(false);
        $payment_order->setDepartment($this->getReference(DepartmentFixture::DEPARTMENT4_REFERENCE));
        $payment_order->setAmount(100);
        $payment_order->setComment('Test');
        $payment_order->getBankInfo()
            ->setAccountOwner('John Doe');
        $payment_order->getBankInfo()
            ->setIban('DE98500105174783924844');
        $payment_order->getBankInfo()
            ->setBic('INGDDEFFXXX');
        $payment_order->getBankInfo()
            ->setStreet('Street 1');
        $payment_order->getBankInfo()
            ->setZipCode('12345');
        $payment_order->getBankInfo()
            ->setCity('Jena');
        $manager->persist($payment_order);

        $payment_order = new PaymentOrder();
        $payment_order->setFirstName('John');
        $payment_order->setLastName('Doe');
        $payment_order->setContactEmail('test@invalid.com');
        $payment_order->setFundingId('');
        $payment_order->setProjectName('Test23');
        $payment_order->setFsrKomResolution(true);
        $payment_order->setDepartment($this->getReference(DepartmentFixture::DEPARTMENT5_REFERENCE));
        $payment_order->setAmount(10000);
        $payment_order->setComment('');
        $payment_order->getBankInfo()
            ->setAccountOwner('John Doe');
        $payment_order->getBankInfo()
            ->setIban('DE98 5001 0517 4783 9248 44');
        $payment_order->getBankInfo()
            ->setStreet('Street 1');
        $payment_order->getBankInfo()
            ->setZipCode('12345');
        $payment_order->getBankInfo()
            ->setCity('Jena');
        $manager->persist($payment_order);

        $manager->flush();
    }
}
