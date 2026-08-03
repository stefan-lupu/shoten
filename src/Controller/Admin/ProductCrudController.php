<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Enum\StockStatus;
use App\Form\ProductImageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCrudController extends AbstractCrudController
{
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

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nume');
        yield TextField::new('slug')->hideOnForm();
        yield AssociationField::new('category', 'Categorie');
        yield ChoiceField::new('stockStatus', 'Status stoc')
            ->setChoices(['În stoc' => StockStatus::InStock, 'La comandă' => StockStatus::OnOrder])
            ->renderAsBadges()
        ;
        yield IntegerField::new('stock', 'Stoc')->setHelp('Relevant doar pentru „În stoc”.');
        yield IntegerField::new('estimatedDays', 'Zile estimate')->setHelp('Relevant doar pentru „La comandă”.')->hideOnIndex();
        yield NumberField::new('price', 'Preț (lei)')->setNumDecimals(2);
        yield TextField::new('origin', 'Origine');
        yield TextareaField::new('description', 'Descriere')->hideOnIndex();
        yield TextField::new('metaTitle', 'Meta title (SEO)')->setRequired(false)->hideOnIndex();
        yield TextareaField::new('metaDescription', 'Meta description (SEO)')->setRequired(false)->hideOnIndex();
        yield CollectionField::new('images', 'Imagini')
            ->setEntryType(ProductImageType::class)
            ->allowAdd()
            ->allowDelete()
            ->hideOnIndex()
        ;
        yield DateTimeField::new('createdAt')->hideOnForm();
    }
}
