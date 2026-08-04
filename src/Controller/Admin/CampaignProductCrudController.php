<?php

namespace App\Controller\Admin;

use App\Entity\CampaignProduct;
use App\Enum\CampaignProductRole;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CampaignProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CampaignProduct::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produs în campanie')
            ->setEntityLabelInPlural('Produse în campanii')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('campaign', 'Campanie')->autocomplete();
        yield AssociationField::new('product', 'Produs')->autocomplete();
        yield ChoiceField::new('role', 'Rol')
            ->setChoices([
                'Țintă (produsul la care se aplică reducerea)' => CampaignProductRole::Target,
                'Trebuie adăugat în coș (declanșează BOGO)' => CampaignProductRole::Trigger,
                'Gratuit automat (cost 0, la finalizare comandă)' => CampaignProductRole::Gift,
                'Parte din bundle' => CampaignProductRole::BundleItem,
            ])
            ->renderAsBadges()
        ;
    }
}
