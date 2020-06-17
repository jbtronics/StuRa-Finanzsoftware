<?php


namespace App\Form;


use App\Entity\Department;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class DepartmentChoiceType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getParent()
    {
        return EntityType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                                   'data_class' => Department::class,
                                   'class' => Department::class,
                                   'placeholder' => 'select.choose_value',
                                   'choice_label' => 'name',
                                    'attr' => [
                                        'class' => 'selectpicker',
                                        'data-live-search' => true,
                                    ]
                               ]);

        $resolver->setDefault('group_by', function (Department $choice, $key, $value) {
            return $this->translator->trans('department.type.' . $choice->getType() ?? 'misc');
        });
    }
}