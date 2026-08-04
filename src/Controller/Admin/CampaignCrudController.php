<?php

namespace App\Controller\Admin;

use App\Entity\Campaign;
use App\Enum\CampaignType;
use App\Enum\DiscountValueType;
use App\Form\CampaignProductType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CampaignCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Campaign::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Campanie')
            ->setEntityLabelInPlural('Campanii')
            ->setDefaultSort(['id' => 'DESC'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nume (intern, pentru admin)');
        yield ChoiceField::new('type', 'Tip')
            ->setChoices([
                'Reducere' => CampaignType::Discount,
                'Cupon' => CampaignType::Coupon,
                'BOGO (cumperi X, primești Y gratis)' => CampaignType::Bogo,
                'Cadou la prag valoric' => CampaignType::GiftThreshold,
                'Bundle' => CampaignType::Bundle,
            ])
            ->renderAsBadges()
        ;
        yield BooleanField::new('isActive', 'Activă');
        yield ChoiceField::new('discountValueType', 'Tip valoare')
            ->setChoices([
                'Procent' => DiscountValueType::Percentage,
                'Sumă fixă (lei)' => DiscountValueType::Fixed,
            ])
            ->setRequired(false)
            ->setHelp('Doar pentru tipul „Reducere” — decide cum se interpretează câmpul „Valoare” de mai jos.')
        ;
        yield NumberField::new('discountValue', 'Valoare')
            ->setRequired(false)
            ->setNumDecimals(2)
            ->setHelp('Reducere: procent sau lei, după „Tip valoare” de mai sus. Cupon/Bundle: mereu lei. Cadou la prag: pragul valoric în lei.')
        ;
        yield TextField::new('couponCode', 'Cod cupon')->setRequired(false)->setHelp('Necesar doar pentru tipul „Cupon”.');
        yield IntegerField::new('maxUses', 'Utilizări maxime')->setRequired(false)->setHelp('Gol = fără limită.');
        yield IntegerField::new('usesCount', 'Utilizări curente')->hideOnForm();
        yield DateTimeField::new('startsAt', 'Începe la')->setRequired(false);
        yield DateTimeField::new('endsAt', 'Se termină la')->setRequired(false);
        yield CollectionField::new('campaignProducts', 'Produse aplicabile')
            ->setEntryType(CampaignProductType::class)
            ->allowAdd()
            ->allowDelete()
            ->setHelp('Aici alegi la ce produse se aplică campania (căutare cu autocomplete). Pentru BOGO: un produs „Trebuie adăugat în coș” (declanșator) și unul „Gratuit automat” (pot fi același, pentru „cumperi 2, plătești 1”). Pentru reduceri: rolul „Țintă”. Pentru bundle: toate produsele cu rolul „Parte din bundle”. Fără produse aici, campania nu se aplică nimic.')
            ->hideOnIndex()
        ;
    }
}
