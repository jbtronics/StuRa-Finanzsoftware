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

namespace App\Repository;

use App\Entity\Department;
use App\Entity\FundingApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FundingApplication|null find($id, $lockMode = null, $lockVersion = null)
 * @method FundingApplication|null findOneBy(array $criteria, array $orderBy = null)
 * @method FundingApplication[]    findAll()
 * @method FundingApplication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FundingApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FundingApplication::class);
    }

    /**
     * Returns the highest funding ID number saved in the database with the given parameters.
     * This means if there is a "M-123-20/21" and a "M-124-20/21", 124 is returned.
     * If no funding applications matching the given paramets is existing yet, null is returned.
     * @param  bool  $external_funding_application
     * @param  string  $year_part
     * @return int|null
     */
    public function getHighestFundingIDNumber(bool $external_funding_application, string $year_part): ?int
    {
        $tmp = $this->createQueryBuilder('a')
            ->select('MAX(a.funding_id.number) as number')
            ->andWhere('a.funding_id.external_funding = :external_funding')
            ->setParameter('external_funding', $external_funding_application)
            ->andWhere('a.funding_id.year_part = :year_part')
            ->setParameter('year_part', $year_part)

            ->getQuery()
            ->getOneOrNullResult();

        //We can only select an element if the return value was not null
        if ($tmp) {
            return $tmp['number'];
        }

        return null;
    }
}