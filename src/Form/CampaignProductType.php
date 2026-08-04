<?php

namespace App\Form;

use App\Entity\CampaignProduct;
use App\Enum\CampaignProductRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', ProductAutocompleteField::class, [
                'label' => 'Produs',
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rol',
                'choices' => [
                    'Țintă (produsul la care se aplică reducerea)' => CampaignProductRole::Target,
                    'Trebuie adăugat în coș (declanșează BOGO)' => CampaignProductRole::Trigger,
                    'Gratuit automat (cost 0, la finalizare comandă)' => CampaignProductRole::Gift,
                    'Parte din bundle' => CampaignProductRole::BundleItem,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CampaignProduct::class,
        ]);
    }
}
