<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, ['label' => 'Nume complet'])
            ->add('phone', TelType::class, ['label' => 'Telefon'])
            ->add('county', TextType::class, ['label' => 'Județ'])
            ->add('city', TextType::class, ['label' => 'Localitate'])
            ->add('street', TextType::class, ['label' => 'Stradă și număr'])
            ->add('postalCode', TextType::class, ['label' => 'Cod poștal'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
