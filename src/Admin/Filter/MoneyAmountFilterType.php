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

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoneyAmountFilterType extends AbstractType
{
    private readonly string $valueType;

    public function __construct(string $valueType = null, private readonly array $valueTypeOptions = [])
    {
        $this->valueType = $valueType ?: NumberType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value2', $options['value_type'], $options['value_type_options'] + [
                'label' => false,
            ]);

        $builder->addModelTransformer(new CallbackTransformer(
            static fn($data) => $data,
            static function (array $data): array {
                if (ComparisonType::BETWEEN === $data['comparison']) {
                    if (null === $data['value'] || '' === $data['value'] || null === $data['value2'] || '' === $data['value2']) {
                        throw new TransformationFailedException('Two values must be provided when "BETWEEN" comparison is selected.');
                    }

                    // make sure value 2 is greater than value 1
                    if ($data['value'] > $data['value2']) {
                        [$data['value'], $data['value2']] = [$data['value2'], $data['value']];
                    }
                }

                $data['value'] *= 100;
                $data['value2'] *= 100;

                return $data;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'value_type' => $this->valueType,
            'value_type_options' => $this->valueTypeOptions,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'ea_numeric_filter';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return ComparisonFilterType::class;
    }
}
