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

namespace App\Admin\Filter;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;

class ConfirmedFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(self::class)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(BooleanFilterType::class);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if (false === $filterDataDto->getValue()) {
            $comparison = 'IS NULL';
            $queryBuilder
                ->andWhere(sprintf('%s.%s %s', $filterDataDto->getEntityAlias(), 'confirmation1.timestamp', $comparison))
                ->orWhere(
                    $queryBuilder->expr()->andX(
                        sprintf('%s.%s %s', $filterDataDto->getEntityAlias(), 'confirmation2.timestamp', $comparison),
                        sprintf('%s.%s > 1', $filterDataDto->getEntityAlias(), 'requiredConfirmations')
                    )
                );
        } else {
            $comparison = 'IS NOT NULL';
            $queryBuilder
                ->andWhere(sprintf('%s.%s %s', $filterDataDto->getEntityAlias(), 'confirmation1.timestamp', $comparison))
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        sprintf('%s.%s %s', $filterDataDto->getEntityAlias(), 'confirmation2.timestamp', $comparison),
                        //The second confirmation is not required, if only one confirmation is required
                        sprintf('%s.%s < 2', $filterDataDto->getEntityAlias(), 'requiredConfirmations')
                    ));
        }
    }
}
