<?php
/*
 * Copyright (C)  2020-2021  Jan Böhmer
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

use App\Entity\PaymentOrder;
use App\Exception\SinglePaymentOrderExceedsLimit;
use App\Services\SEPAExport\PaymentOrderSEPAExporter;
use App\Services\SEPAExport\SEPAExportGroupAndSplitHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SEPAExportGroupAndSplitHelperTest extends WebTestCase
{
    /** @var SEPAExportGroupAndSplitHelper */
    private $service;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->service = self::$container->get(SEPAExportGroupAndSplitHelper::class);
    }

    public function testSplitPaymentOrdersMaxNumber(): void
    {
        $payment_order1 = (new PaymentOrder())->setAmount(10);
        $payment_order2 = (new PaymentOrder())->setAmount(350);
        $payment_order3 = (new PaymentOrder())->setAmount(666);
        $payment_order4 = (new PaymentOrder())->setAmount(1000);
        $payment_order5 = (new PaymentOrder())->setAmount(123);

        //If we are below or equal to the limit nothing should happen (just put it into an array)
        self::assertSame([[$payment_order1, $payment_order2]], $this->service->splitPaymentOrders([$payment_order1, $payment_order2], 3, 1000000));
        self::assertSame([[$payment_order1, $payment_order2]], $this->service->splitPaymentOrders([$payment_order1, $payment_order2], 2, 1000000));

        //If we are above the limit, the array should be split
        self::assertSame([[$payment_order1, $payment_order2, $payment_order3], [$payment_order4, $payment_order5]],
            $this->service->splitPaymentOrders(
                [$payment_order1, $payment_order2, $payment_order3, $payment_order4, $payment_order5],
                3,
                1000000
            )
        );
        self::assertSame([[$payment_order1, $payment_order2], [$payment_order3, $payment_order4], [$payment_order5]],
            $this->service->splitPaymentOrders(
                [$payment_order1, $payment_order2, $payment_order3, $payment_order4, $payment_order5],
                2,
                1000000
            )
        );
        self::assertSame([[$payment_order1, $payment_order2, $payment_order3, $payment_order4], [$payment_order5]],
            $this->service->splitPaymentOrders(
                [$payment_order1, $payment_order2, $payment_order3, $payment_order4, $payment_order5],
                4,
                1000000
            )
        );
    }

    public function testSplitPaymentOrdersThrowsSinglePaymentExceedsLimit1(): void
    {
        $this->expectException(SinglePaymentOrderExceedsLimit::class);
        $payment_order1 = (new PaymentOrder())->setAmount(10);
        $payment_order2 = (new PaymentOrder())->setAmount(350);
        $payment_order3 = (new PaymentOrder())->setAmount(666);

        $this->service->splitPaymentOrders([$payment_order1, $payment_order2, $payment_order3], 5, 400);
    }

    public function testSplitPaymentOrdersThrowsSinglePaymentExceedsLimit2(): void
    {
        $this->expectException(SinglePaymentOrderExceedsLimit::class);
        $payment_order1 = (new PaymentOrder())->setAmount(500);

        $this->service->splitPaymentOrders([$payment_order1], 5, 400);
    }


    public function testSplitPaymentOrdersMaxAmount1(): void
    {
        $payment_order1 = (new PaymentOrder())->setAmount(10)->setProjectName('P1');
        $payment_order2 = (new PaymentOrder())->setAmount(10)->setProjectName('P2');
        $payment_order3 = (new PaymentOrder())->setAmount(10)->setProjectName('P3');
        $payment_order4 = (new PaymentOrder())->setAmount(10)->setProjectName('P4');

        //If we don't pass the limit, nothing should happen
        self::assertSame([
            [$payment_order1, $payment_order2, $payment_order3, $payment_order4]
        ], $this->service->splitPaymentOrders([$payment_order1, $payment_order2, $payment_order3, $payment_order4], 10, 40));

        //Ensure that the limit is an inclusive limit (so the elements are split 20 and 10)
        self::assertSame([
            [$payment_order4, $payment_order3, $payment_order2],
            [$payment_order1]
        ], $this->service->splitPaymentOrders([$payment_order1, $payment_order2, $payment_order3, $payment_order4], 10, 30));
    }

    public function testSplitPaymentOrdersMaxAmount2(): void
    {
        $payment_order1 = (new PaymentOrder())->setAmount(10)->setProjectName('P1');
        $payment_order2 = (new PaymentOrder())->setAmount(9)->setProjectName('P2');
        $payment_order3 = (new PaymentOrder())->setAmount(7)->setProjectName('P3');
        $payment_order4 = (new PaymentOrder())->setAmount(8)->setProjectName('P4');

        self::assertSame([
            [$payment_order1, $payment_order2, $payment_order4],
            [$payment_order3],
        ], $this->service->splitPaymentOrders([$payment_order1, $payment_order2, $payment_order3, $payment_order4], 10, 30));
    }

    public function testSplitPaymentOrdersMaxAmount3(): void
    {
        $payment_order1 = (new PaymentOrder())->setAmount(10)->setProjectName('P1');
        $payment_order2 = (new PaymentOrder())->setAmount(10)->setProjectName('P2');
        $payment_order3 = (new PaymentOrder())->setAmount(10)->setProjectName('P3');
        $payment_order4 = (new PaymentOrder())->setAmount(10)->setProjectName('P4');

        self::assertSame([
            [$payment_order4, $payment_order3],
            [$payment_order2, $payment_order1],
        ], $this->service->splitPaymentOrders([$payment_order1, $payment_order2, $payment_order3, $payment_order4], 10, 29));
    }

    public function testSplitPaymentOrdersMaxAmount4(): void
    {
        $payment_order1 = (new PaymentOrder())->setAmount(10)->setProjectName('P1');
        $payment_order2 = (new PaymentOrder())->setAmount(10)->setProjectName('P2');
        $payment_order3 = (new PaymentOrder())->setAmount(10)->setProjectName('P3');
        $payment_order4 = (new PaymentOrder())->setAmount(10)->setProjectName('P4');

        self::assertEquals([
            [$payment_order4],
            [$payment_order1],
            [$payment_order3],
            [$payment_order2],
        ], $this->service->splitPaymentOrders([$payment_order1, $payment_order2, $payment_order3, $payment_order4], 15, 15));
    }

    public function testSplitPaymentOrdersMaxAmount5(): void
    {
        //Test with big amounts

        $payment_order1 = (new PaymentOrder())->setAmount(500000)->setProjectName('P1'); //5k €
        $payment_order2 = (new PaymentOrder())->setAmount(300000)->setProjectName('P2'); //3k €
        $payment_order3 = (new PaymentOrder())->setAmount(600000)->setProjectName('P3'); //6k €
        $payment_order4 = (new PaymentOrder())->setAmount(30000)->setProjectName('P4'); //300 €
        $payment_order5 = (new PaymentOrder())->setAmount(700000)->setProjectName('P5'); //7k €

        self::assertEquals([
            [$payment_order5],
            [$payment_order3],
            [$payment_order1, $payment_order2, $payment_order4]
        ], $this->service->splitPaymentOrders([$payment_order1, $payment_order2, $payment_order3, $payment_order4, $payment_order5], 15, 1000000));
    }

    public function testSplitPaymentOrdersMaxAmount6(): void
    {
        $payment_order1 = (new PaymentOrder())->setAmount(14)->setProjectName('P1');
        $payment_order2 = (new PaymentOrder())->setAmount(10)->setProjectName('P2');
        $payment_order3 = (new PaymentOrder())->setAmount(10)->setProjectName('P3');
        $payment_order4 = (new PaymentOrder())->setAmount(10)->setProjectName('P4');

        self::assertEquals([
            [$payment_order1],
            [$payment_order3],
            [$payment_order4],
            [$payment_order2],
        ], $this->service->splitPaymentOrders([$payment_order1, $payment_order2, $payment_order3, $payment_order4], 15, 15));
    }


    public function testCalculateSumAmountOfPaymentOrders(): void
    {
        $payment_order1 = (new PaymentOrder())->setAmount(10);
        $payment_order2 = (new PaymentOrder())->setAmount(350);
        $payment_order3 = (new PaymentOrder())->setAmount(666);
        $payment_order4 = (new PaymentOrder())->setAmount(1000);

        //The sum of an empty array is zero
        self::assertSame(0, $this->service->calculateSumAmountOfPaymentOrders([]));

        self::assertSame(10, $this->service->calculateSumAmountOfPaymentOrders([$payment_order1]));
        self::assertSame(676, $this->service->calculateSumAmountOfPaymentOrders([$payment_order3, $payment_order1]));
        self::assertSame(2026, $this->service->calculateSumAmountOfPaymentOrders([$payment_order1, $payment_order2, $payment_order3, $payment_order4]));
    }

    public function testSortPaymentOrderArrayByAmount(): void
    {
        $payment_order1 = (new PaymentOrder())->setAmount(10);
        $payment_order2 = (new PaymentOrder())->setAmount(350);
        $payment_order3 = (new PaymentOrder())->setAmount(666);
        $payment_order4 = (new PaymentOrder())->setAmount(1000);

        self::assertSame([$payment_order1, $payment_order2, $payment_order3, $payment_order4],
            $this->service->sortPaymentOrderArrayByAmount([$payment_order4, $payment_order2, $payment_order1, $payment_order3]));

        self::assertSame([$payment_order4, $payment_order3, $payment_order2, $payment_order1],
            $this->service->sortPaymentOrderArrayByAmount([$payment_order3, $payment_order4, $payment_order2, $payment_order1], false));

        self::assertSame([$payment_order4, $payment_order1],
            $this->service->sortPaymentOrderArrayByAmount([$payment_order1, $payment_order4], false));

        self::assertSame([$payment_order1, $payment_order4],
            $this->service->sortPaymentOrderArrayByAmount([$payment_order1, $payment_order4], true));
    }
}
