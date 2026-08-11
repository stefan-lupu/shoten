<?php

namespace App\Form;

use App\Dto\CheckoutData;
use App\Enum\PaymentMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Checkout fără cont (guest). Colectează emailul de contact + adresa de
 * livrare inline (guestul nu are adrese salvate). Plata cu cardul nu apare
 * aici — vezi CheckoutController: trece prin poarta de plată, care cere cont.
 */
class GuestCheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $notBlank = static fn (string $msg) => [new Assert\NotBlank(message: $msg)];

        $builder
            ->add('guestEmail', EmailType::class, [
                'label' => 'Email',
                'help' => 'Aici primești confirmarea comenzii și actualizările de livrare.',
                'constraints' => [
                    new Assert\NotBlank(message: 'Introdu adresa de email.'),
                    new Assert\Email(message: 'Adresa de email nu este validă.'),
                ],
            ])
            ->add('fullName', TextType::class, ['label' => 'Nume complet', 'constraints' => $notBlank('Introdu numele complet.')])
            ->add('phone', TelType::class, ['label' => 'Telefon', 'constraints' => $notBlank('Introdu numărul de telefon.')])
            ->add('county', TextType::class, ['label' => 'Județ', 'constraints' => $notBlank('Introdu județul.')])
            ->add('city', TextType::class, ['label' => 'Localitate', 'constraints' => $notBlank('Introdu localitatea.')])
            ->add('street', TextType::class, ['label' => 'Stradă și număr', 'constraints' => $notBlank('Introdu strada și numărul.')])
            ->add('postalCode', TextType::class, ['label' => 'Cod poștal', 'constraints' => $notBlank('Introdu codul poștal.')])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Metodă de plată',
                'expanded' => true,
                // Fără card pentru guest — plata cu cardul cere cont (vezi CheckoutController).
                'choices' => [
                    'Ramburs la livrare' => PaymentMethod::Cod,
                    'Transfer bancar' => PaymentMethod::BankTransfer,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CheckoutData::class]);
    }
}
