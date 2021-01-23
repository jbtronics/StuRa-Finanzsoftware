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

namespace App\Tests\Doctrine;

use App\Doctrine\FundingIDGeneratorListener;
use App\Entity\FundingApplication;
use App\Services\EmailConfirmation\ConfirmationEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FundingIDGeneratorListenerTest extends WebTestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::$container->get(EntityManagerInterface::class);
    }

    public function testThatFundingIDIsInserted()
    {
        $funding_application = new FundingApplication();
        $funding_application->setRequestedAmount(100)
        ->setFundingIntention("Test");

        self::assertNull($funding_application->getFundingId());

        $this->em->persist($funding_application);
        $this->em->flush();

        self::assertNotNull($funding_application->getFundingId());
    }

}
