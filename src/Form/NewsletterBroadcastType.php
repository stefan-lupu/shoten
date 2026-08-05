<?php

namespace App\Form;

use App\Dto\NewsletterBroadcast;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsletterBroadcastType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, ['label' => 'Subiect'])
            ->add('body', TextareaType::class, ['label' => 'Conținut', 'attr' => ['rows' => 12]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NewsletterBroadcast::class,
        ]);
    }
}
