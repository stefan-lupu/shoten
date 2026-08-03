<?php

namespace App\Controller\Admin;

use App\Entity\Campaign;
use App\Enum\CampaignType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
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
            ->setHelp('index', 'Produsele legate de o campanie (target/trigger/gift/bundle_item) se gestionează din meniul „Produse în campanii”.')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nume (intern, pentru admin)');
        yield ChoiceField::new('type', 'Tip')
            ->setChoices([
                'Reducere procentuală' => CampaignType::PercentageDiscount,
                'Reducere fixă' => CampaignType::FixedDiscount,
                'Cupon' => CampaignType::Coupon,
                'BOGO (cumperi X, primești Y gratis)' => CampaignType::Bogo,
                'Cadou la prag valoric' => CampaignType::GiftThreshold,
                'Bundle' => CampaignType::Bundle,
            ])
            ->renderAsBadges()
        ;
        yield BooleanField::new('isActive', 'Activă');
        yield NumberField::new('discountValue', 'Valoare (% sau lei, după tip)')->setRequired(false)->setNumDecimals(2);
        yield TextField::new('couponCode', 'Cod cupon')->setRequired(false)->setHelp('Necesar doar pentru tipul „Cupon”.');
        yield IntegerField::new('maxUses', 'Utilizări maxime')->setRequired(false)->setHelp('Gol = fără limită.');
        yield IntegerField::new('usesCount', 'Utilizări curente')->hideOnForm();
        yield DateTimeField::new('startsAt', 'Începe la')->setRequired(false);
        yield DateTimeField::new('endsAt', 'Se termină la')->setRequired(false);
    }
}
