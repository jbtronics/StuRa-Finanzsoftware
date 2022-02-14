<?php
/*
 * Copyright (C)  2020-2022  Jan Böhmer
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

namespace App\Services\SEPAExport;

use App\Controller\Admin\PaymentOrderCrudController;
use App\Entity\PaymentOrder;
use App\Entity\SEPAExport;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class SEPAExportAdminHelper
{
    private $entityManager;
    private $adminUrlGenerator;

    public function __construct(EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    /**
     * Returns all payments order that were not marked as factually correct.
     * @param  SEPAExport  $SEPAExport
     * @return PaymentOrder[]
     */
    public function getNotFactuallyCorrectPaymentOrders(SEPAExport $SEPAExport): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        return $qb->select('p')
            ->from(PaymentOrder::class, 'p')
            ->leftJoin('p.associated_sepa_exports', 's')
            ->where('s = :sepa_export')
            ->andwhere('p.factually_correct = false')
            ->setParameter('sepa_export', $SEPAExport)
            ->getQuery()->getResult();
    }

    /**
     * Returns all payments order that were not marked as factually correct.
     * @param  SEPAExport  $SEPAExport
     * @return PaymentOrder[]
     */
    public function getNotMathematicallyCorrectPaymentOrders(SEPAExport $SEPAExport): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        return $qb->select('p')
            ->from(PaymentOrder::class, 'p')
            ->leftJoin('p.associated_sepa_exports', 's')
            ->where('s = :sepa_export')
            ->andwhere('p.mathematically_correct = false')
            ->setParameter('sepa_export', $SEPAExport)
            ->getQuery()->getResult();
    }

    /**
     * Returns all payments order that were not marked as factually correct.
     * @param  SEPAExport  $SEPAExport
     * @return PaymentOrder[]
     */
    public function getAlreadyBookedPaymentOrders(SEPAExport $SEPAExport): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        return $qb->select('p')
            ->from(PaymentOrder::class, 'p')
            ->leftJoin('p.associated_sepa_exports', 's')
            ->where('s = :sepa_export')
            ->andwhere('p.booking_date IS NOT null')
            ->setParameter('sepa_export', $SEPAExport)
            ->getQuery()->getResult();
    }

    /**
     * @param  PaymentOrder[]  $paymentOrders
     * @return string
     */
    public function getPaymentOrdersFlashText(array $paymentOrders): string
    {
        $tmp = "";

        foreach($paymentOrders as $paymentOrder) {
            $link = $this->adminUrlGenerator->setController(PaymentOrderCrudController::class)->setAction('detail')->setEntityId($paymentOrder->getId());
            $text = $paymentOrder->getIDString() . ': ' . $paymentOrder->getProjectName() . ' (' . $paymentOrder->getDepartment()->getName() . '), ' . $paymentOrder->getAmountString() . ' €';
            $tmp .= sprintf('<br><a href="%s">%s</a>', $link, $text);
        }

        return $tmp;
    }

}