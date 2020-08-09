<?php


namespace App\Admin\Filter;


use App\Entity\Department;
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

        foreach(Department::ALLOWED_TYPES as $type) {
            $choices['department.type.' . $type ] = $type;
        }

        return (new self())
            ->setFilterFqcn(__CLASS__)
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
        $queryBuilder->andWhere('department.type = :department_type')->setParameter('department_type',$filterDataDto->getValue());
        $queryBuilder->leftJoin($filterDataDto->getEntityAlias() . '.department', 'department');
    }
}