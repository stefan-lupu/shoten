<?php

namespace App\Controller\Admin;

use App\Entity\ShippingSettings;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Setări cu un singur rând (creat de migrare) — nu se pot crea/șterge
 * rânduri, doar edita cel existent.
 */
#[IsGranted('ROLE_ADMIN')]
class ShippingSettingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ShippingSettings::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Setări transport')
            ->setEntityLabelInPlural('Setări transport')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield NumberField::new('cost', 'Cost transport (lei)')->setNumDecimals(2);
        yield NumberField::new('freeShippingThreshold', 'Prag livrare gratuită (lei)')
            ->setNumDecimals(2)
            ->setRequired(false)
            ->setHelp('Comenzile cu subtotal peste această valoare au transport gratuit. Gol = niciodată gratuit.')
        ;
    }
}
