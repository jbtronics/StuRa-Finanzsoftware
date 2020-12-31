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

namespace App\Tests\Entity;

use App\Entity\BankAccount;
use App\Entity\Embeddable\PayeeInfo;
use App\Entity\PaymentOrder;
use PHPUnit\Framework\TestCase;

class BankAccountTest extends TestCase
{

    public function testGetExportAccountName(): void
    {
        $bank_account = new BankAccount();
        $bank_account->setName("Name");
        //Test getAccountName if no explizit Account name is set
        self::assertSame("Name", $bank_account->getExportAccountName());
        //Ensure that it also works if account name only consists of whitespace characters
        $bank_account->setAccountName("  ");
        self::assertSame("Name", $bank_account->getExportAccountName());

        //Explicitly set Export Account name
        $bank_account->setAccountName("Test");
        self::assertSame("Test", $bank_account->getExportAccountName());
    }

    public function testToString(): void
    {
        $bank_account = new BankAccount();
        $bank_account->setName("Test Account");
        $bank_account->setIban("DE04500105172482835112");

        self::assertSame("Test Account [DE04500105172482835112]", (string) $bank_account);

        //Ensure that it works if name and IBAN are empty (it is ugly however)
        $bank_account = new BankAccount();
        self::assertSame(" []", (string) $bank_account);
    }

    public function testGetIBANWithoutSpaces(): void
    {
        $payeeInfo = new BankAccount();

        //If IBAN already contains no spaces it should not be changed
        $payeeInfo->setIban("NL93ABNA6055981262");
        self::assertSame("NL93ABNA6055981262", $payeeInfo->getIbanWithoutSpaces());

        //If it contains spaces, they must be removed
        $payeeInfo->setIban("NL93 ABNA 6055 9812 62");
        self::assertSame("NL93ABNA6055981262", $payeeInfo->getIbanWithoutSpaces());
    }
}
