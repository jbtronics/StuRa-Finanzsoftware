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
        self::assertTrue($client->getResponse()->isRedirect('/'));

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

        self::assertTrue($client->getResponse()->isSuccessful());

        $buttonCrawlerNode = $crawler->selectButton('Absenden und weiteren Auftrag erstellen');
        $form = $buttonCrawlerNode->form();
        $this->fillPaymentOrderFormData($form);
        $client->submit($form);

        //Success submit does not redirect but returns a new form
        self::assertTrue($client->getResponse()->isSuccessful());

        //Assert that 3 emails are sent (2 confirmation + 1 notification email)
        self::assertEmailCount(3);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertEmailCount(0);
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

    public function testConfirmation(): void
    {
    }
}
