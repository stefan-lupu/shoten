<?php

namespace App\Form;

use App\Dto\CheckoutData;
use App\Enum\PaymentMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutType extends AbstractType
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
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Metodă de plată',
                'expanded' => true,
                'choices' => [
                    'Card online' => PaymentMethod::Card,
                    'Ramburs la livrare' => PaymentMethod::Cod,
                    'Transfer bancar' => PaymentMethod::BankTransfer,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CheckoutData::class,
        ]);
    }
}
