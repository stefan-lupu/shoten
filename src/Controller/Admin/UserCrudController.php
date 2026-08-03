<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilizator')
            ->setEntityLabelInPlural('Utilizatori')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email', 'Email');
        yield TextField::new('firstName', 'Prenume');
        yield TextField::new('lastName', 'Nume');
        yield TextField::new('phone', 'Telefon')->setRequired(false)->hideOnIndex();
        yield ChoiceField::new('roles', 'Roluri')
            ->setChoices([
                'Administrator' => 'ROLE_ADMIN',
                'Comenzi' => 'ROLE_ORDERS_MANAGER',
                'Financiar' => 'ROLE_FINANCE_MANAGER',
                'Gestionar' => 'ROLE_STOCK_MANAGER',
            ])
            ->allowMultipleChoices()
            ->renderAsBadges()
            ->setHelp('Toți utilizatorii au implicit acces de client (ROLE_USER). Un utilizator poate avea mai multe roluri de admin simultan; „Administrator” le include automat pe toate celelalte.')
        ;
        yield TextField::new('plainPassword', 'Parolă')
            ->setFormType(PasswordType::class)
            ->setFormTypeOptions(['required' => Crud::PAGE_NEW === $pageName])
            ->setRequired(Crud::PAGE_NEW === $pageName)
            ->onlyOnForms()
            ->setHelp(Crud::PAGE_EDIT === $pageName ? 'Lasă gol pentru a păstra parola actuală.' : '')
        ;
        yield DateTimeField::new('createdAt', 'Creat la')->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPlainPassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPlainPassword($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPlainPassword(User $user): void
    {
        if (null === $user->getPlainPassword() || '' === $user->getPlainPassword()) {
            return;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPlainPassword()));
        $user->setPlainPassword(null);
    }
}
