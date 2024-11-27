<?php

declare(strict_types=1);


namespace App\Services\Statistics;

use App\Entity\PaymentOrder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final readonly class PaymentOrderStatistics
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    private function addFromToRange(QueryBuilder $qb, null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null, string $field = "po.creation_date"): void
    {
        if ($from !== null) {
            if (!$from instanceof \DateTimeInterface) {
                $from = new \DateTime($from);
            }
            $qb->andWhere(sprintf('%s >= :from', $field))
                ->setParameter('from', $from);
        }

        if ($to !== null) {
            if (!$to instanceof \DateTimeInterface) {
                $to = new \DateTime($to);
            }
            $qb->andWhere(sprintf('%s <= :to', $field))
                ->setParameter('to', $to);
        }
    }

    /**
     * Returns the number of submitted payment orders within the given time range.
     * @return int
     */
    public function getSubmittedPaymentOrdersCount(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(po.id)')
            ->from(PaymentOrder::class, 'po');

        $this->addFromToRange($qb, $from, $to);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the number of booked payment orders within the given time range.
     * @param  \DateTimeInterface|string|null  $from
     * @param  \DateTimeInterface|string|null  $to
     * @return int
     * @throws \DateMalformedStringException
     */
    public function getBookedPaymentOrdersCount(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(po.id)')
            ->from(PaymentOrder::class, 'po')
            ->where('po.booking_date IS NOT NULL');

        $this->addFromToRange($qb, $from, $to, "po.booking_date");

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the number of payment orders that have been checked for mathematical correctness within the given time range.
     * @param  \DateTimeInterface|string|null  $from
     * @param  \DateTimeInterface|string|null  $to
     * @return int
     * @throws \DateMalformedStringException
     */
    public function getMathematicallyCheckedPaymentOrdersCount(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(po.id)')
            ->from(PaymentOrder::class, 'po')
            ->where('po.mathematically_correct.checked = true');

        $this->addFromToRange($qb, $from, $to, "po.mathematically_correct.timestamp");

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the number of payment orders that have been checked for factual correctness within the given time range.
     * @param  \DateTimeInterface|string|null  $from
     * @param  \DateTimeInterface|string|null  $to
     * @return int
     * @throws \DateMalformedStringException
     */
    public function getFactuallyCheckedPaymentOrdersCount(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(po.id)')
            ->from(PaymentOrder::class, 'po')
            ->where('po.factually_correct.checked = true');

        $this->addFromToRange($qb, $from, $to, "po.factually_correct.timestamp");

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the total value of submitted payment orders within the given time range.
     * @return float
     */
    public function getPaymentOrdersTotalValue(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('SUM(po.amount)')
            ->from(PaymentOrder::class, 'po');

        $this->addFromToRange($qb, $from, $to);

        // We need to divide the result by 100 because the value is stored in cents.
        return ((float) $qb->getQuery()->getSingleScalarResult()) / 100;
    }

    public function getPaymentOrdersAverageValue(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('AVG(po.amount)')
            ->from(PaymentOrder::class, 'po');

        $this->addFromToRange($qb, $from, $to);

        // We need to divide the result by 100 because the value is stored in cents.
        return ((float) $qb->getQuery()->getSingleScalarResult()) / 100;
    }

    public function getPaymentOrdersStdDevValue(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('STDDEV(po.amount)')
            ->from(PaymentOrder::class, 'po');

        $this->addFromToRange($qb, $from, $to);

        // We need to divide the result by 100 because the value is stored in cents.
        return ((float) $qb->getQuery()->getSingleScalarResult()) / 100;
    }

    public function getPaymentOrdersMaxValue(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('MAX(po.amount)')
            ->from(PaymentOrder::class, 'po');

        $this->addFromToRange($qb, $from, $to);

        // We need to divide the result by 100 because the value is stored in cents.
        return ((float) $qb->getQuery()->getSingleScalarResult()) / 100;
    }

    public function getPaymentOrdersMinValue(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('MIN(po.amount)')
            ->from(PaymentOrder::class, 'po');

        $this->addFromToRange($qb, $from, $to);

        // We need to divide the result by 100 because the value is stored in cents.
        return ((float) $qb->getQuery()->getSingleScalarResult()) / 100;
    }

    /**
     * Returns the average time (in days) it took to process a payment order from submission to booking within the given time range.
     * @param  \DateTimeInterface|string|null  $from
     * @param  \DateTimeInterface|string|null  $to
     * @return float
     */
    public function getAverageProcessingTime(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): ?float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('AVG(UNIX_TIMESTAMP(po.booking_date) - UNIX_TIMESTAMP(po.creation_date))')
            ->from(PaymentOrder::class, 'po')
            ->where('po.booking_date IS NOT NULL');

        $this->addFromToRange($qb, $from, $to, 'po.booking_date');

        $res =  $qb->getQuery()->getSingleScalarResult();
        if ($res === null) {
            return null;
        }

        //Res is in seconds, we need to convert it to days
        return (float)$res / 86400;
    }

    /**
     * Returns the standard deviation of the time (in days) it took to process a payment order from submission to booking within the given time range.
     * @param  \DateTimeInterface|null  $from
     * @param  \DateTimeInterface|null  $to
     * @return float|null
     */
    public function getStddevProcessingTime(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): ?float
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('STDDEV(UNIX_TIMESTAMP(po.booking_date) - UNIX_TIMESTAMP(po.creation_date))')
            ->from(PaymentOrder::class, 'po')
            ->where('po.booking_date IS NOT NULL');

        $this->addFromToRange($qb, $from, $to, 'po.booking_date');

        $res =  $qb->getQuery()->getSingleScalarResult();
        if ($res === null) {
            return null;
        }

        //Res is in seconds, we need to convert it to days
        return (float)$res / 86400;
    }
}