<?php

namespace App\Controller\Admin;

use App\Entity\CampaignProduct;
use App\Enum\CampaignProductRole;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

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
        yield AssociationField::new('campaign', 'Campanie');
        yield AssociationField::new('product', 'Produs');
        yield ChoiceField::new('role', 'Rol')
            ->setChoices([
                'Țintă (produsul la care se aplică reducerea)' => CampaignProductRole::Target,
                'Declanșator (BOGO)' => CampaignProductRole::Trigger,
                'Cadou' => CampaignProductRole::Gift,
                'Parte din bundle' => CampaignProductRole::BundleItem,
            ])
            ->renderAsBadges()
        ;
    }
}
