<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\WholesaleStatus;
use App\Service\StoreConfig;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
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
            ->setHelp('Toți utilizatorii au implicit acces de client (ROLE_USER). Un utilizator poate avea mai multe roluri de admin simultan; „Administrator” le include automat pe toate celelalte. Accesul angro (ROLE_WHOLESALE) nu se editează aici — doar prin acțiunile de Aprobă/Respinge de mai jos.')
        ;
        yield TextField::new('plainPassword', 'Parolă')
            ->setFormType(PasswordType::class)
            ->setFormTypeOptions(['required' => Crud::PAGE_NEW === $pageName])
            ->setRequired(Crud::PAGE_NEW === $pageName)
            ->onlyOnForms()
            ->setHelp(Crud::PAGE_EDIT === $pageName ? 'Lasă gol pentru a păstra parola actuală.' : '')
        ;
        yield ChoiceField::new('wholesaleStatus', 'Status angro')
            ->setChoices([
                'Fără cerere' => WholesaleStatus::None,
                'În așteptare' => WholesaleStatus::Pending,
                'Aprobat' => WholesaleStatus::Approved,
                'Respins' => WholesaleStatus::Rejected,
            ])
            ->renderAsBadges([
                'none' => 'secondary',
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
            ])
            ->hideOnForm()
        ;
        yield TextField::new('companyName', 'Firmă')->hideOnIndex();
        yield TextField::new('companyCui', 'CUI')->hideOnIndex();
        yield TextField::new('companyRegCom', 'Nr. Reg. Com.')->hideOnIndex();
        yield TextField::new('companyAddress', 'Adresă firmă')->hideOnIndex();
        yield DateTimeField::new('wholesaleRequestedAt', 'Cerere angro trimisă la')->hideOnIndex()->hideOnForm();
        yield DateTimeField::new('createdAt', 'Creat la')->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('wholesaleStatus', 'Status angro')->setChoices([
                'Fără cerere' => WholesaleStatus::None,
                'În așteptare' => WholesaleStatus::Pending,
                'Aprobat' => WholesaleStatus::Approved,
                'Respins' => WholesaleStatus::Rejected,
            ]))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $approveWholesale = Action::new('approveWholesale', 'Aprobă cont angro', 'fa fa-check')
            // linkToUrl (nu linkToCrudAction) ca să putem adăuga un token CSRF —
            // acțiunea dă acces real la prețuri angro, nu trebuie declanșabilă
            // printr-un simplu GET forjat.
            ->linkToUrl(fn (User $user) => $this->adminUrlGenerator
                ->unsetAll()
                ->setController(self::class)
                ->setAction('approveWholesale')
                ->setEntityId($user->getId())
                ->set('_token', $this->csrfTokenManager->getToken('approve_wholesale_'.$user->getId())->getValue())
                ->generateUrl())
            ->displayIf(static fn (User $user) => WholesaleStatus::Pending === $user->getWholesaleStatus())
            ->setCssClass('text-success')
            ->setHtmlAttributes(['onclick' => "return confirm('Sigur aprobi acest cont angro? Va avea acces imediat la prețurile pe cantitate.')"])
        ;
        $rejectWholesale = Action::new('rejectWholesale', 'Respinge cont angro', 'fa fa-xmark')
            ->linkToCrudAction('rejectWholesaleForm')
            ->displayIf(static fn (User $user) => WholesaleStatus::Pending === $user->getWholesaleStatus())
            ->setCssClass('text-danger')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $approveWholesale)
            ->add(Crud::PAGE_INDEX, $rejectWholesale)
            ->add(Crud::PAGE_DETAIL, $approveWholesale)
            ->add(Crud::PAGE_DETAIL, $rejectWholesale)
        ;
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

    #[AdminRoute(path: '/approve-wholesale', name: 'approve_wholesale')]
    #[IsGranted('ROLE_ADMIN')]
    public function approveWholesale(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, StoreConfig $store): RedirectResponse
    {
        $user = $entityManager->getRepository(User::class)->find($request->query->get('entityId'));
        if ($user && !$this->isCsrfTokenValid('approve_wholesale_'.$user->getId(), $request->query->get('_token'))) {
            $this->addFlash('danger', 'Token de securitate invalid sau expirat — reîncearcă din pagina utilizatorului.');

            return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($user->getId())->generateUrl());
        }
        if ($user && WholesaleStatus::Pending === $user->getWholesaleStatus()) {
            $user->setWholesaleStatus(WholesaleStatus::Approved);
            $user->setRoles(array_values(array_unique([...$user->getRoles(), 'ROLE_WHOLESALE'])));
            $entityManager->flush();

            $email = (new TemplatedEmail())
                ->from(new EmailAddress($store->email, $store->name))
                ->to($user->getEmail())
                ->subject('Contul tău angro a fost aprobat')
                ->htmlTemplate('emails/wholesale_approved.html.twig')
                ->context(['user' => $user])
            ;
            $mailer->send($email);

            $this->addFlash('success', 'Cont angro aprobat.');
        }

        return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($user?->getId())->generateUrl());
    }

    #[AdminRoute(path: '/reject-wholesale', name: 'reject_wholesale')]
    #[IsGranted('ROLE_ADMIN')]
    public function rejectWholesaleForm(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, StoreConfig $store): Response
    {
        $user = $entityManager->getRepository(User::class)->find($request->query->get('entityId'));
        if (!$user) {
            throw $this->createNotFoundException('Utilizator inexistent.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reject_wholesale_'.$user->getId(), $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token de securitate invalid — reîncearcă.');

                return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($user->getId())->generateUrl());
            }

            if (WholesaleStatus::Pending === $user->getWholesaleStatus()) {
                $reason = trim((string) $request->request->get('reason'));
                $user->setWholesaleStatus(WholesaleStatus::Rejected);
                $user->setRoles(array_values(array_diff($user->getRoles(), ['ROLE_WHOLESALE'])));
                $entityManager->flush();

                $email = (new TemplatedEmail())
                    ->from(new EmailAddress($store->email, $store->name))
                    ->to($user->getEmail())
                    ->subject('Cererea ta de cont angro a fost respinsă')
                    ->htmlTemplate('emails/wholesale_rejected.html.twig')
                    ->context(['user' => $user, 'reason' => $reason])
                ;
                $mailer->send($email);

                $this->addFlash('success', 'Cerere de cont angro respinsă.');
            }

            return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($user->getId())->generateUrl());
        }

        return $this->render('admin/wholesale_reject.html.twig', ['wholesaleUser' => $user]);
    }
}
