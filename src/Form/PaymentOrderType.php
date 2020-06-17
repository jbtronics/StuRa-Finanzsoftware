<?php


namespace App\Form;


use App\Entity\Department;
use App\Entity\Embeddable\BankAccountInfo;
use App\Entity\PaymentOrder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('first_name', TextType::class, [
            'label' => 'payment_order.first_name.label',
            'attr' => [
                'placeholder' => 'payment_order.first_name.placeholder'
            ]
        ]);

        $builder->add('last_name', TextType::class, [
            'label' => 'payment_order.last_name.label',
            'attr' => [
                'placeholder' => 'payment_order.last_name.placeholder'
            ]
        ]);

        $builder->add('project_name', TextType::class, [
            'label' => 'payment_order.project_name.label',
            'attr' => [
                'placeholder' => 'payment_order.project_name.placeholder',
            ],
        ]);

        $builder->add('department', DepartmentChoiceType::class, [
            'label' => 'payment_order.department.label',
        ]);

        $builder->add('amount', MoneyType::class, [
            'label' => 'payment_order.amount.label',
            'divisor' => 100,
            'currency' => 'EUR',
            'attr' => [
                'placeholder' => 'payment_order.amount.placeholder'
            ]
        ]);

        $builder->add('bank_info', BankAccountInfoType::class, [
            'label' => false
        ]);

        $builder->add('submit', SubmitType::class);
        $builder->add('reset', ResetType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', PaymentOrder::class);
    }
}