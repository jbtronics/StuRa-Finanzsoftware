<?php

namespace App\DataFixtures;

use App\Entity\PaymentOrder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PaymentOrderFixture extends Fixture
{
    public function __construct()
    {
    }

    public function load(ObjectManager $manager): void
    {
        $payment_order = new PaymentOrder();
        $payment_order->setFirstName('John');
        $payment_order->setLastName('Doe');
        $payment_order->setSubmitterEmail('test@invalid.com');
        $payment_order->setFundingId('M-123-2020');
        $payment_order->setProjectName('Test');
        $payment_order->setFsrKomResolution(true);
        $payment_order->setDepartment($this->getReference(DepartmentFixture::DEPARTMENT3_REFERENCE));
        $payment_order->setAmount(12340);
        $payment_order->setComment('Test');
        $payment_order->setConfirm1Token(password_hash('token1', PASSWORD_DEFAULT));
        $payment_order->setConfirm2Token(password_hash('token2', PASSWORD_DEFAULT));
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

        $this->addFiles($payment_order);
        $manager->persist($payment_order);

        $payment_order = new PaymentOrder();
        $payment_order->setFirstName('John');
        $payment_order->setLastName('Doe');
        $payment_order->setSubmitterEmail('test@invalid.com');
        $payment_order->setFundingId('');
        $payment_order->setProjectName('Test');
        $payment_order->setFsrKomResolution(false);
        $payment_order->setDepartment($this->getReference(DepartmentFixture::DEPARTMENT2_REFERENCE));
        $payment_order->setAmount(12340);
        $payment_order->setComment('Test');
        $payment_order->setConfirm1Token(password_hash('token1', PASSWORD_DEFAULT));
        $payment_order->setConfirm2Token(null);
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

        $this->addFiles($payment_order);
        $manager->persist($payment_order);

        $payment_order = new PaymentOrder();
        $payment_order->setFirstName('John');
        $payment_order->setLastName('Doe');
        $payment_order->setSubmitterEmail('test@invalid.com');
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

        $this->addFiles($payment_order);
        $manager->persist($payment_order);

        $payment_order = new PaymentOrder();
        $payment_order->setFirstName('John');
        $payment_order->setLastName('Doe');
        $payment_order->setSubmitterEmail('test@invalid.com');
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

        $this->addFiles($payment_order);
        $manager->persist($payment_order);

        $manager->flush();
    }

    private function addFiles(PaymentOrder $paymentOrder): void
    {
        $source_file = realpath(__DIR__.'/../../tests/data/form/upload.pdf');
        //We have to create a copy of our source, or the file will be deleted when the files are uploaded...
        $target_file = tempnam(sys_get_temp_dir(), 'stura');
        copy($source_file, $target_file);

        $file = new UploadedFile($target_file, 'form.pdf', null, null, true);
        $paymentOrder->setPrintedFormFile($file);

        //Do the same thing for References

        $source_file = realpath(__DIR__.'/../../tests/data/form/upload.pdf');
        //We have to create a copy of our source, or the file will be deleted when the files are uploaded...
        $target_file = tempnam(sys_get_temp_dir(), 'stura');
        copy($source_file, $target_file);

        $file = new UploadedFile($target_file, 'form.pdf', null, null, true);
        $paymentOrder->setReferencesFile($file);
    }
}
