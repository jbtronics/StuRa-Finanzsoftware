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

use App\Entity\Department;
use App\Entity\DepartmentTypes;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class DepartmentTypeFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        $choices = [];

        foreach (DepartmentTypes::cases() as $type) {
            $choices['department.type.'.$type->value] = $type->value;
        }

        $choices['department.type.section_misc'] = 'section_misc';

        return (new self())
            ->setFilterFqcn(self::class)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ChoiceType::class)
            ->setFormTypeOption('choices', $choices);
    }

    public function apply(
        QueryBuilder $queryBuilder,
        FilterDataDto $filterDataDto,
        ?FieldDto $fieldDto,
        EntityDto $entityDto
    ): void {
        $value = $filterDataDto->getValue();

        if ('section_misc' === $value) {
            $queryBuilder->andWhere('department.type = :misc')
                ->setParameter('misc', 'misc');
            $queryBuilder->orWhere('department.type = :section')
                ->setParameter('section', 'section');
        } else {
            $queryBuilder->andWhere('department.type = :department_type')
                ->setParameter(
                    'department_type',
                    $filterDataDto->getValue()
                );
        }
        $queryBuilder->leftJoin($filterDataDto->getEntityAlias().'.department', 'department');
    }
}
