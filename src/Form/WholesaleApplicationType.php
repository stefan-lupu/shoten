<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Legat direct de User, dar expune doar câmpurile de firmă — Symfony Form
 * atinge exclusiv câmpurile adăugate explicit mai jos, restul contului
 * (email, parolă, roluri) rămâne neatins la submit.
 */
class WholesaleApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'Denumire firmă',
                'constraints' => [new Assert\NotBlank(message: 'Introdu denumirea firmei.')],
            ])
            ->add('companyCui', TextType::class, [
                'label' => 'CUI',
                'constraints' => [new Assert\NotBlank(message: 'Introdu CUI-ul firmei.')],
            ])
            ->add('companyRegCom', TextType::class, [
                'label' => 'Nr. Reg. Com.',
                'constraints' => [new Assert\NotBlank(message: 'Introdu numărul de înregistrare la Registrul Comerțului.')],
            ])
            ->add('companyAddress', TextType::class, [
                'label' => 'Adresa firmei',
                'constraints' => [new Assert\NotBlank(message: 'Introdu adresa firmei.')],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
