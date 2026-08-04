<?php

namespace App\Form;

use App\Dto\CheckoutData;
use App\Entity\Address;
use App\Enum\PaymentMethod;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address', EntityType::class, [
                'label' => 'Adresă de livrare',
                'class' => Address::class,
                'choices' => $options['addresses'],
                'choice_label' => static fn (Address $a) => sprintf(
                    '%s — %s, %s, %s, %s, tel. %s',
                    $a->getFullName(),
                    $a->getStreet(),
                    $a->getCity(),
                    $a->getCounty(),
                    $a->getPostalCode(),
                    $a->getPhone(),
                ),
                'expanded' => true,
            ])
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
        $resolver
            ->setDefaults(['data_class' => CheckoutData::class])
            ->setRequired('addresses')
            ->setAllowedTypes('addresses', 'array')
        ;
    }
}
