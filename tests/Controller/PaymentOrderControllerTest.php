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

namespace App\Tests\Controller;

use App\Entity\PaymentOrder;
use App\Repository\PaymentOrderRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

/**
 * @group DB
 */
class PaymentOrderControllerTest extends WebTestCase
{
    protected $data_dir;

    public function setUp(): void
    {
        $this->data_dir = realpath(__DIR__.'/../data/form');
    }

    public function testNewFormSubmit(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        $crawler = $client->request('GET', '/payment_order/new');

        self::assertTrue($client->getResponse()->isSuccessful());

        $buttonCrawlerNode = $crawler->selectButton('Auftrag absenden');
        $form = $buttonCrawlerNode->form();
        $this->fillPaymentOrderFormData($form);
        $client->submit($form);

        //Success submit returns to homepage
        self::assertResponseRedirects('/');

        //Assert that 3 emails are sent (2 confirmation + 1 notification email)
        self::assertEmailCount(3);

        //Check if an element was created
        $repo = self::$container->get(PaymentOrderRepository::class);
        /** @var PaymentOrder $new_payment_order */
        $new_payment_order = $repo->findOneBy([
            'project_name' => 'Form Test',
        ]);

        //Do some basic validation
        self::assertSame('John', $new_payment_order->getFirstName());
        self::assertSame('DE68500105175596424738', $new_payment_order->getBankInfo()->getIbanWithoutSpaces());

        //Test if file was uploaded and put into correct place
        $references_file = $new_payment_order->getReferencesFile();
        self::assertNotNull($references_file);
        self::assertStringContainsString('/uploads/payment_orders', str_replace('\\', '/', $references_file->getRealPath()));

        //Test if a form was created
        self::assertNotNull($new_payment_order->getPrintedFormFile());
    }

    public function testNewFormSubmitAndNew(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        $crawler = $client->request('GET', '/payment_order/new');

        self::assertResponseIsSuccessful();

        $buttonCrawlerNode = $crawler->selectButton('Absenden und weiteren Auftrag erstellen');
        $form = $buttonCrawlerNode->form();
        $this->fillPaymentOrderFormData($form);
        $client->submit($form);

        //Success submit does not redirect but returns a new form
        self::assertResponseIsSuccessful();

        //Assert that 3 emails are sent (2 confirmation + 1 notification email)
        self::assertEmailCount(3);

        $buttonCrawlerNode = $crawler->selectButton('Absenden und weiteren Auftrag erstellen');
        $form = $buttonCrawlerNode->form();
        //Most inputs should stay the same...
        self::assertInputValueSame('payment_order[first_name]', 'John');
        //Except project_name, amount and other fields
        self::assertInputValueSame('payment_order[amount]', '');
        self::assertInputValueSame('payment_order[project_name]', '');
    }

    public function testNewFormEmptySubmit(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        $crawler = $client->request('GET', '/payment_order/new');

        self::assertTrue($client->getResponse()->isSuccessful());

        $buttonCrawlerNode = $crawler->selectButton('Auftrag absenden');
        $form = $buttonCrawlerNode->form();

        //We have to set the department field or we will run into many exceptions...
        $form->setValues([
            'payment_order[department]' => '3',
        ]);

        $client->submit($form);

        //The form should return successfully (without exceptions) as form errors are rendered
        //But the form is not redirected.
        self::assertResponseIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testNewFormBlockedDepartment(): void
    {
        //Form submission must fail if the department is blocked

        $client = self::createClient();
        $client->catchExceptions(false);

        $crawler = $client->request('GET', '/payment_order/new');

        self::assertResponseIsSuccessful();

        $buttonCrawlerNode = $crawler->selectButton('Auftrag absenden');
        $form = $buttonCrawlerNode->form();
        $this->fillPaymentOrderFormData($form);

        //Department 2 is blocked
        $form->setValues([
            'payment_order[department]' => '2',
        ]);

        $client->submit($form);

        //The form should return successfully (without exceptions) as form errors are rendered
        self::assertResponseIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testNewFormBlockedDepartmentInvalidBlockedToken(): void
    {
        //Form submission must fail if the department is blocked

        $client = self::createClient();
        $client->catchExceptions(false);

        $crawler = $client->request('GET', '/payment_order/new?blocked_token=invalid');

        self::assertResponseIsSuccessful();

        $buttonCrawlerNode = $crawler->selectButton('Auftrag absenden');
        $form = $buttonCrawlerNode->form();
        $this->fillPaymentOrderFormData($form);

        //Department 2 is blocked
        $form->setValues([
            'payment_order[department]' => '2',
        ]);

        $client->submit($form);

        //The form should return successfully (without exceptions) as form errors are rendered
        self::assertResponseIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testNewFormBlockedDepartmentCorrectToken(): void
    {
        //With the correct token form must be submittable even if the department is blocked

        $client = self::createClient();
        $client->catchExceptions(false);

        $crawler = $client->request('GET', '/payment_order/new?blocked_token=token1');

        self::assertResponseIsSuccessful();

        $buttonCrawlerNode = $crawler->selectButton('Auftrag absenden');
        $form = $buttonCrawlerNode->form();
        $this->fillPaymentOrderFormData($form);

        $form->setValues([
            'payment_order[department]' => '2',
        ]);

        $client->submit($form);
        //Success submit does not redirect but returns a new form
        self::assertResponseRedirects('/');

        //Assert that 3 emails are sent (2 confirmation + 1 notification email)
        self::assertEmailCount(3);
    }

    protected function fillPaymentOrderFormData(Form $form): void
    {
        $form->setValues([
            'payment_order[department]' => '3',
            'payment_order[first_name]' => 'John',
            'payment_order[last_name]' => 'Doe',
            'payment_order[contact_email]' => 'j.doe@invalid.com',
            'payment_order[project_name]' => 'Form Test',
            'payment_order[amount]' => '31,12',
            'payment_order[resolution_date]' => '2020-12-31',
            'payment_order[comment]' => '',
            'payment_order[bank_info][account_owner]' => 'John Doe',
            'payment_order[bank_info][street]' => 'Street 1',
            'payment_order[bank_info][zip_code]' => '12345',
            'payment_order[bank_info][city]' => 'City',
            'payment_order[bank_info][iban]' => 'DE68500105175596424738',
            'payment_order[bank_info][bic]' => '',
            'payment_order[bank_info][bank_name]' => 'Bank',
        ]);

        //Upload a PDF
        $form['payment_order[references_file][file]']->upload($this->data_dir.'/upload.pdf');
    }

    public function testConfirmationInvalidToken(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        //Invalid token must redirect
        $client->request('GET', '/payment_order/1/confirm?confirm=1&token=invalid');
        self::assertResponseRedirects('/');

        //Same must be true for second confirmation
        $client->request('GET', '/payment_order/1/confirm?confirm=1&token=invalid');
        self::assertResponseRedirects('/');

        //And for an invalid confirm step
        $client->request('GET', '/payment_order/1/confirm?confirm=3&token=invalid');
        self::assertResponseRedirects('/');

        //Or if we just call the route without params
        $client->request('GET', '/payment_order/1/confirm');
        self::assertResponseRedirects('/');
    }

    public function testConfirmation1(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        $client->request('GET', '/payment_order/1/confirm?confirm=1&token=token1');

        //With correct token page must be rendered successfully
        self::assertResponseIsSuccessful();

        $client->submitForm('Zahlungsauftrag bestätigen', [
            'payment_order_confirmation[check_1]' => '1',
            'payment_order_confirmation[check_2]' => '1',
            'payment_order_confirmation[check_3]' => '1',
        ]);

        self::assertResponseIsSuccessful();

        $repo = self::$container->get(PaymentOrderRepository::class);
        /** @var PaymentOrder $payment_order Retrieve the payment order we just confirmed */
        $payment_order = $repo->find(1);

        //And check if it was marked as confirmed
        self::assertNotNull($payment_order->getConfirm1Timestamp());
    }

    public function testConfirmation2(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        $client->request('GET', '/payment_order/1/confirm?confirm=2&token=token2');

        //With correct token page must be rendered successfully
        self::assertResponseIsSuccessful();

        $client->submitForm('Zahlungsauftrag bestätigen', [
            'payment_order_confirmation[check_1]' => '1',
            'payment_order_confirmation[check_2]' => '1',
            'payment_order_confirmation[check_3]' => '1',
        ]);

        self::assertResponseIsSuccessful();

        $repo = self::$container->get(PaymentOrderRepository::class);
        /** @var PaymentOrder $payment_order Retrieve the payment order we just confirmed */
        $payment_order = $repo->find(1);

        //And check if it was marked as confirmed
        self::assertNotNull($payment_order->getConfirm2Timestamp());
    }

    public function testConfirmation1WithoutAllCheckboxesChecked(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        $repo = self::$container->get(PaymentOrderRepository::class);
        /** @var PaymentOrder $payment_order Retrieve the payment order we just confirmed */
        $payment_order = $repo->find(1);

        $client->request('GET', '/payment_order/1/confirm?confirm=1&token=token1');

        //With correct token page must be rendered successfully
        self::assertResponseIsSuccessful();

        //Submit form with one check mark missing
        $client->submitForm('Zahlungsauftrag bestätigen', [
            'payment_order_confirmation[check_1]' => '1',
            'payment_order_confirmation[check_2]' => '1',
            //'payment_order_confirmation[check_3]' => null,
        ]);

        self::assertResponseIsSuccessful();

        //If a check mark was missing the payment order must not be confirmed
        self::assertNull($payment_order->getConfirm1Timestamp());
    }
}
