<?php

namespace App\Form;

use App\Entity\ProductWholesaleTier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductWholesaleTierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('minQuantity', IntegerType::class, ['label' => 'De la cantitate'])
            ->add('unitPrice', NumberType::class, ['label' => 'Preț/buc (lei)', 'scale' => 2])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductWholesaleTier::class,
        ]);
    }
}
