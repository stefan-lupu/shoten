<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Service\StoreConfig;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ORDERS_VIEWER')]
class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Comandă')
            ->setEntityLabelInPlural('Comenzi')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('user', 'Client')->hideOnForm();
        yield ChoiceField::new('status', 'Status')
            ->setChoices([
                'În așteptare' => OrderStatus::Pending,
                'Confirmată' => OrderStatus::Confirmed,
                'Expediată' => OrderStatus::Shipped,
                'Livrată' => OrderStatus::Delivered,
                'Anulată' => OrderStatus::Cancelled,
            ])
            ->renderAsBadges([
                'pending' => 'secondary',
                'confirmed' => 'primary',
                'shipped' => 'info',
                'delivered' => 'success',
                'cancelled' => 'danger',
            ])
            ->hideOnForm()
        ;
        yield ChoiceField::new('paymentMethod', 'Metodă plată')
            ->setChoices([
                'Card' => PaymentMethod::Card,
                'Ramburs' => PaymentMethod::Cod,
                'Transfer bancar' => PaymentMethod::BankTransfer,
            ])
            ->hideOnForm()
        ;
        yield ChoiceField::new('paymentStatus', 'Status plată')
            ->setChoices([
                'În așteptare' => PaymentStatus::Pending,
                'Plătită' => PaymentStatus::Paid,
                'Eșuată' => PaymentStatus::Failed,
            ])
            ->renderAsBadges([
                'pending' => 'secondary',
                'paid' => 'success',
                'failed' => 'danger',
            ])
            ->hideOnForm()
        ;
        yield NumberField::new('total', 'Total (lei)')->setNumDecimals(2)->hideOnForm();
        yield TextField::new('couponCode', 'Cod cupon')->hideOnIndex()->hideOnForm();
        yield TextField::new('shippingFullName', 'Nume destinatar')->hideOnIndex()->hideOnForm();
        yield TextField::new('shippingPhone', 'Telefon')->hideOnIndex()->hideOnForm();
        yield TextField::new('shippingStreet', 'Stradă')->hideOnIndex()->hideOnForm();
        yield TextField::new('shippingCity', 'Localitate')->hideOnIndex()->hideOnForm();
        yield TextField::new('shippingCounty', 'Județ')->hideOnIndex()->hideOnForm();
        yield TextField::new('shippingPostalCode', 'Cod poștal')->hideOnIndex()->hideOnForm();
        yield DateTimeField::new('createdAt', 'Data')->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'În așteptare' => OrderStatus::Pending,
                'Confirmată' => OrderStatus::Confirmed,
                'Expediată' => OrderStatus::Shipped,
                'Livrată' => OrderStatus::Delivered,
                'Anulată' => OrderStatus::Cancelled,
            ]))
            ->add(ChoiceFilter::new('paymentMethod')->setChoices([
                'Card' => PaymentMethod::Card,
                'Ramburs' => PaymentMethod::Cod,
                'Transfer bancar' => PaymentMethod::BankTransfer,
            ]))
            ->add(DateTimeFilter::new('createdAt', 'Data'))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // Statusul nu se editează liber dintr-un dropdown — doar prin
        // acțiuni dedicate, ca să nu poți pune o comandă într-o stare
        // inconsistentă din greșeală (ex: "livrată" fără să fi fost plătită).
        $markPaid = Action::new('markPaid', 'Marchează ca plătită', 'fa fa-money-bill')
            ->linkToCrudAction('markPaid')
            ->displayIf(static fn (Order $order) => PaymentStatus::Paid !== $order->getPaymentStatus())
            ->setCssClass('text-success')
        ;
        $markShipped = Action::new('markShipped', 'Marchează ca expediată', 'fa fa-truck')
            ->linkToCrudAction('markShipped')
            ->displayIf(static fn (Order $order) => !\in_array($order->getStatus(), [OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Cancelled], true))
        ;

        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, $markPaid)
            ->add(Crud::PAGE_INDEX, $markShipped)
            ->add(Crud::PAGE_DETAIL, $markPaid)
            ->add(Crud::PAGE_DETAIL, $markShipped)
            // Financiarul confirmă plata, Comenzi gestionează expedierea — fiecare
            // vede/poate declanșa doar acțiunea din aria lui (ROLE_ADMIN le are pe amândouă).
            ->setPermission('markPaid', 'ROLE_FINANCE_MANAGER')
            ->setPermission('markShipped', 'ROLE_ORDERS_MANAGER')
        ;
    }

    #[AdminRoute(path: '/mark-paid', name: 'mark_paid')]
    #[IsGranted('ROLE_FINANCE_MANAGER')]
    public function markPaid(
        Request $request,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator,
        MailerInterface $mailer,
        StoreConfig $store,
    ): RedirectResponse {
        $order = $entityManager->getRepository(Order::class)->find($request->query->get('entityId'));
        if ($order && PaymentStatus::Paid !== $order->getPaymentStatus()) {
            $order->setPaymentStatus(PaymentStatus::Paid);
            if (OrderStatus::Pending === $order->getStatus()) {
                $order->setStatus(OrderStatus::Confirmed);
            }
            $entityManager->flush();

            $email = (new TemplatedEmail())
                ->from(new EmailAddress($store->email, $store->name))
                ->to($order->getUser()->getEmail())
                ->subject(sprintf('Plata pentru comanda #%d a fost confirmată', $order->getId()))
                ->htmlTemplate('emails/payment_confirmed.html.twig')
                ->context(['order' => $order])
            ;
            $mailer->send($email);
        }

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($order?->getId())->generateUrl());
    }

    #[AdminRoute(path: '/mark-shipped', name: 'mark_shipped')]
    #[IsGranted('ROLE_ORDERS_MANAGER')]
    public function markShipped(Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        $order = $entityManager->getRepository(Order::class)->find($request->query->get('entityId'));
        if ($order) {
            $order->setStatus(OrderStatus::Shipped);
            $entityManager->flush();
        }

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($order?->getId())->generateUrl());
    }
}
