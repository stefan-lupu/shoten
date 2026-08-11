<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Enum\StockStatus;
use App\Form\ProductImageType;
use App\Form\ProductWholesaleTierType;
use App\Service\StockNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STOCK_MANAGER')]
class ProductCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly StockNotificationService $stockNotificationService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produs')
            ->setEntityLabelInPlural('Produse')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // Gestionarul de stoc (ROLE_STOCK_MANAGER fără ROLE_ADMIN) poate
        // ajusta stocul produselor existente, dar nu poate crea/șterge produse.
        return $actions
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // Fără ROLE_ADMIN, doar câmpurile de stoc rămân editabile — restul
        // sunt afișate needitabil (disabled), nu ascunse, ca gestionarul
        // să vadă tot contextul produsului la ajustarea stocului.
        $canEditAll = $this->isGranted('ROLE_ADMIN');

        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nume')->setFormTypeOption('disabled', !$canEditAll);
        yield TextField::new('slug')->hideOnForm();
        yield AssociationField::new('category', 'Categorie')->setFormTypeOption('disabled', !$canEditAll);
        yield ChoiceField::new('stockStatus', 'Status stoc')
            ->setChoices(['În stoc' => StockStatus::InStock, 'La comandă' => StockStatus::OnOrder])
            ->renderAsBadges()
        ;
        yield IntegerField::new('stock', 'Stoc')->setHelp('Relevant doar pentru „În stoc”.');
        yield IntegerField::new('estimatedDays', 'Zile estimate')->setHelp('Relevant doar pentru „La comandă”.')->hideOnIndex();
        yield NumberField::new('price', 'Preț (lei)')->setNumDecimals(2)->setFormTypeOption('disabled', !$canEditAll);
        yield BooleanField::new('isPromoted', 'Promovat')
            ->renderAsSwitch(Crud::PAGE_INDEX !== $pageName)
            ->setFormTypeOption('disabled', !$canEditAll)
            ->setHelp('Prioritate în căutări/listări + chenar auriu. La expirarea perioadei se debifează automat.')
        ;
        yield DateTimeField::new('promotedFrom', 'Promovat de la')->setRequired(false)->hideOnIndex()->setFormTypeOption('disabled', !$canEditAll)->setHelp('Opțional. Gol = de acum.');
        yield DateTimeField::new('promotedUntil', 'Promovat până la')->setRequired(false)->hideOnIndex()->setFormTypeOption('disabled', !$canEditAll)->setHelp('Opțional. Gol = fără limită.');
        yield TextField::new('origin', 'Origine')->setFormTypeOption('disabled', !$canEditAll);
        yield TextField::new('internalCode', 'Cod intern')->setRequired(false)->setFormTypeOption('disabled', !$canEditAll);
        yield TextField::new('externalCode', 'Cod extern')->setRequired(false)->setFormTypeOption('disabled', !$canEditAll);
        yield TextareaField::new('description', 'Descriere')->hideOnIndex()->setFormTypeOption('disabled', !$canEditAll);
        yield TextField::new('metaTitle', 'Meta title (SEO)')->setRequired(false)->hideOnIndex()->setFormTypeOption('disabled', !$canEditAll);
        yield TextareaField::new('metaDescription', 'Meta description (SEO)')->setRequired(false)->hideOnIndex()->setFormTypeOption('disabled', !$canEditAll);
        yield CollectionField::new('images', 'Imagini')
            ->setEntryType(ProductImageType::class)
            ->allowAdd($canEditAll)
            ->allowDelete($canEditAll)
            ->hideOnIndex()
        ;
        yield CollectionField::new('wholesaleTiers', 'Preț pe cantitate (angro)')
            ->setEntryType(ProductWholesaleTierType::class)
            ->allowAdd($canEditAll)
            ->allowDelete($canEditAll)
            ->hideOnIndex()
            ->setHelp('Praguri de cantitate → preț/buc, vizibile doar clienților cu cont angro aprobat. Adaugă-le în ordine crescătoare a cantității, cu prețul scăzând (sau rămânând constant) la fiecare prag.')
        ;
        yield DateTimeField::new('createdAt')->hideOnForm();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Valoarea din DB înainte ca formularul să-și suprascrie datele pe entitate,
        // ca să detectăm exact tranziția 0 → disponibil (nu doar orice modificare de stoc).
        $previousStock = (int) ($entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance)['stock'] ?? 0);

        parent::updateEntity($entityManager, $entityInstance);

        if ($entityInstance instanceof Product) {
            $this->stockNotificationService->notifyIfBackInStock($entityInstance, $previousStock);
        }
    }
}
