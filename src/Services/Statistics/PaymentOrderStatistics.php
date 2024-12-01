<?php

declare(strict_types=1);


namespace App\Services\Statistics;

use App\Entity\PaymentOrder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Helpers to retrieve various statistics about payment orders.
 * The results get cached for 1 hour.
 */
final readonly class PaymentOrderStatistics
{
    //Cache time-to-live in seconds (1 hour)
    private const CACHE_TTL = 3600;

    public function __construct(private EntityManagerInterface $entityManager, private CacheInterface $cache)
    {
    }

    /**
     * Caches the result of a query for a given key (function name) and time range.
     * @param  string  $key
     * @param  \DateTimeInterface|string|null  $from
     * @param  \DateTimeInterface|string|null  $to
     * @param  \Closure  $callback
     * @return mixed
     */
    private function cached(string $key, null|\DateTimeInterface|string $from, null|\DateTimeInterface|string $to, \Closure $callback): mixed
    {
        //Do not cache if datetime objects were passed directly (as we cannot really find them again in the cache)
        if ($from instanceof \DateTimeInterface || $to instanceof \DateTimeInterface) {
            return $callback($from, $to);
        }

        $cacheKey = sprintf('po_statistics_%s_%s_%s', $key, $from ?? '0', $to ?? '0');
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback, $from, $to) {
            $item->expiresAfter(3600);
            return $callback($from, $to);
        });
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
        return $this->cached('submittedCount', $from, $to, function (null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('COUNT(po.id)')
                ->from(PaymentOrder::class, 'po');

            $this->addFromToRange($qb, $from, $to);

            return (int) $qb->getQuery()->getSingleScalarResult();
        });
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
        return $this->cached('bookedCount', $from, $to, function (null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('COUNT(po.id)')
                ->from(PaymentOrder::class, 'po')
                ->where('po.booking_date IS NOT NULL');

            $this->addFromToRange($qb, $from, $to, "po.booking_date");

            return (int) $qb->getQuery()->getSingleScalarResult();
        });
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
        return $this->cached('mathematicallyCheckedCount', $from, $to, function (null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('COUNT(po.id)')
                ->from(PaymentOrder::class, 'po')
                ->where('po.mathematically_correct.checked = true');

            $this->addFromToRange($qb, $from, $to, "po.mathematically_correct.timestamp");

            return (int) $qb->getQuery()->getSingleScalarResult();
        });
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
        return $this->cached('factuallyCheckedCount', $from, $to, function (null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('COUNT(po.id)')
                ->from(PaymentOrder::class, 'po')
                ->where('po.factually_correct.checked = true');

            $this->addFromToRange($qb, $from, $to, "po.factually_correct.timestamp");

            return (int) $qb->getQuery()->getSingleScalarResult();
        });
    }

    /**
     * Returns the total value of submitted payment orders within the given time range.
     * @return float
     */
    public function getPaymentOrdersTotalValue(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): float
    {
        return $this->cached('totalValue', $from, $to, function (null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('SUM(po.amount)')
                ->from(PaymentOrder::class, 'po');

            $this->addFromToRange($qb, $from, $to);

            // We need to divide the result by 100 because the value is stored in cents.
            return ((float) $qb->getQuery()->getSingleScalarResult()) / 100;
        });
    }

    public function getPaymentOrdersAverageValue(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): float
    {
        return $this->cached('averageValue', $from, $to, function (null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('AVG(po.amount)')
                ->from(PaymentOrder::class, 'po');

            $this->addFromToRange($qb, $from, $to);

            // We need to divide the result by 100 because the value is stored in cents.
            return ((float) $qb->getQuery()->getSingleScalarResult()) / 100;
        });
    }

    public function getPaymentOrdersStdDevValue(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): float
    {
        return $this->cached('stdDevValue', $from, $to, function (null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('STDDEV(po.amount)')
                ->from(PaymentOrder::class, 'po');

            $this->addFromToRange($qb, $from, $to);

            // We need to divide the result by 100 because the value is stored in cents.
            return ((float) $qb->getQuery()->getSingleScalarResult()) / 100;
        });
    }

    public function getPaymentOrdersMaxValue(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): float
    {
        return $this->cached('maxValue', $from, $to, function (null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('MAX(po.amount)')
                ->from(PaymentOrder::class, 'po');

            $this->addFromToRange($qb, $from, $to);

            // We need to divide the result by 100 because the value is stored in cents.
            return ((float) $qb->getQuery()->getSingleScalarResult()) / 100;
        });
    }

    public function getPaymentOrdersMinValue(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): float
    {
        return $this->cached('minValue', $from, $to, function (null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('MIN(po.amount)')
                ->from(PaymentOrder::class, 'po');

            $this->addFromToRange($qb, $from, $to);

            // We need to divide the result by 100 because the value is stored in cents.
            return ((float) $qb->getQuery()->getSingleScalarResult()) / 100;
        });
    }

    /**
     * Returns the average time (in days) it took to process a payment order from submission to booking within the given time range.
     * @param  \DateTimeInterface|string|null  $from
     * @param  \DateTimeInterface|string|null  $to
     * @return float
     */
    public function getAverageProcessingTime(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): ?float
    {
        return $this->cached('averageProcessingTime', $from, $to, function (null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null) {
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
        });
    }

    /**
     * Returns the standard deviation of the time (in days) it took to process a payment order from submission to booking within the given time range.
     * @param  \DateTimeInterface|null  $from
     * @param  \DateTimeInterface|null  $to
     * @return float|null
     */
    public function getStddevProcessingTime(null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null): ?float
    {
        return $this->cached('stddevProcessingTime', $from, $to, function (null|\DateTimeInterface|string $from = null, null|\DateTimeInterface|string $to = null) {
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
        });
    }
}