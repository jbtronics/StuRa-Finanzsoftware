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

namespace App\Tests\Services\SEPAExport;

use App\Entity\BankAccount;
use App\Entity\Department;
use App\Entity\PaymentOrder;
use App\Tests\PaymentOrderTestingHelper;
use DOMDocument;
use DOMNode;

trait PaymentOrderSEPAExporterTestHelperTrait
{
    /**
     * Returns an array of 3 PaymentOrders that can be used for testing.
     * They have associated departments and bank accounts.
     *
     * @return PaymentOrder[]
     */
    private function getTestPaymentOrders(): array
    {
        [$department1, $department2, $department3] = $this->getTestDepartments();

        //Generate multiple payment orders
        $payment_order1 = PaymentOrderTestingHelper::getDummyPaymentOrder(1);
        $payment_order1->setAmount(12340);
        $payment_order1->setDepartment($department1);
        $payment_order1->getBankInfo()
            ->setAccountOwner('John Doe')
            ->setIban('DE09 8200 0000 0083 0015 03')
            ->setBic('MARKDEF1820')
            ->setReference('Test payment #1');

        $payment_order2 = PaymentOrderTestingHelper::getDummyPaymentOrder(2);
        $payment_order2->setAmount(10);

        $payment_order2->setDepartment($department2);
        $payment_order2->getBankInfo()
            ->setAccountOwner('Ümläute Teßt') //Test special chars
            //Without spaces
            ->setIban('DE09500105175649523697')
            //Empty BIC
            ->setBic('')
            //Special chars
            ->setReference('Test payment #2 My party@$Place%!&§"()=+*-:. /\?');

        //Generate multiple payment orders
        $payment_order3 = PaymentOrderTestingHelper::getDummyPaymentOrder(3);
        $payment_order3->setAmount(123); //12.30€
        $payment_order3->setDepartment($department3);
        $payment_order3->getBankInfo()
            ->setAccountOwner('Max Musterman')
            //Test foreign IBAN
            ->setIban('AT77 3400 0246 4353 5933')
            ->setBic('')
            ->setReference('Test payment #3');

        return [$payment_order1, $payment_order2, $payment_order3];
    }

    /**
     * Returns an array of 3 Departments that have an bankAccount associated.
     *
     * @return Department[]
     */
    private function getTestDepartments(): array
    {
        [$bank_account1, $bank_account2] = $this->getTestBankAccounts();

        $department1 = new Department();
        $department1->setName('Department1')
            ->setBankAccount($bank_account1);

        $department2 = new Department();
        $department2->setName('Department2')
            ->setBankAccount($bank_account2);

        $department3 = new Department();
        $department3->setName('Department3')
            ->setBankAccount($bank_account1);

        return [$department1, $department2, $department3];
    }

    /**
     * Returns an array of 3 BankAccounts that can be used for testing.
     *
     * @return BankAccount[]
     */
    private function getTestBankAccounts(): array
    {
        //Bank accounts must have an ID or grouping will not work...
        $bank_account1 = new class() extends BankAccount {
            public function getId(): ?int
            {
                return 1;
            }
        };
        $bank_account1->setName('Bank Account 1')
            ->setIban('DE97 6605 0101 0000 1234 56')
            ->setBic('KARSDE66XXX')
            ->setAccountName('Max Mustermann');

        $bank_account2 = new class() extends BankAccount {
            public function getId(): ?int
            {
                return 2;
            }
        };
        $bank_account2->setName('Bank Account 2')
            ->setIban('DE84 6605 0101 0000 1299 95')
            ->setBic('KARSDE66XXX')
            //Empty account name
            ->setAccountName('');

        return [$bank_account1, $bank_account2];
    }

    /**
     * Normalizes the given SEPA-XML by changing MsgID, PmtInfId and ReqdExctnDt to a common value.
     * This way SEPA XML files can be compared.
     */
    protected static function normalizeSEPAXML(DOMDocument $sepaXML): void
    {
        //Normalize MessageID
        $msg_ids = $sepaXML->getElementsByTagName('MsgId');
        foreach ($msg_ids as $msg_id) {
            /** @var DOMNode $msg_id */
            $msg_id->nodeValue = 'Message ID';
        }

        //Normalize Payment initiator name, as it contains the software version info
        $init_partys = $sepaXML->getElementsByTagName('InitgPty');
        foreach ($init_partys as $init_party) {
            /** @var DOMNode $init_party */
            //First child should always be the Nm tag
            $init_party->firstChild->nodeValue = 'Initiator Party';
        }

        //Normalize Creation date
        $dates = $sepaXML->getElementsByTagName('CreDtTm');
        foreach ($dates as $date) {
            /** @var DOMNode $date */
            $date->nodeValue = '2020-12-29T14:15:09Z';
        }

        //Normalize execution date
        $dates = $sepaXML->getElementsByTagName('ReqdExctnDt');
        foreach ($dates as $date) {
            /** @var DOMNode $date */
            $date->nodeValue = '2020-12-29';
        }

        //Payment Info ID
        $pmts = $sepaXML->getElementsByTagName('PmtInfId');
        foreach ($pmts as $pmt) {
            /** @var DOMNode $pmt */
            $pmt->nodeValue = 'Payment';
        }
    }
}