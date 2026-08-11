<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use App\Service\StoreConfig;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ORDERS_VIEWER')]
class OrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

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
            // Include emailul clientului (asociația user), nu doar coloanele proprii ale comenzii.
            ->setSearchFields(['id', 'shippingFullName', 'shippingPhone', 'trackingNumber', 'user.email', 'guestEmail'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('user', 'Client')->hideOnForm();
        // Comenzile guest nu au cont — emailul de contact e aici. Gol la comenzile cu cont.
        yield TextField::new('guestEmail', 'Email (guest)')->hideOnForm();
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
                'Rambursată' => PaymentStatus::Refunded,
            ])
            ->renderAsBadges([
                'pending' => 'secondary',
                'paid' => 'success',
                'failed' => 'danger',
                'refunded' => 'warning',
            ])
            ->hideOnForm()
        ;
        yield NumberField::new('total', 'Total (lei)')->setNumDecimals(2)->hideOnForm();
        // Atribuit automat la prima emitere a facturii (vezi InvoiceNumberAllocator) —
        // afișat needitabil, ca reper contabil.
        yield TextField::new('invoiceLabel', 'Factură')->hideOnForm()->setHelp('Seria + numărul fiscal, atribuit la prima descărcare a facturii.');
        // Setat automat la plasarea comenzii (vezi OrderService::placeOrder) —
        // needitabil aici ca să nu se decupleze de datele billing* de mai jos.
        yield BooleanField::new('isWholesaleOrder', 'Angro')->renderAsSwitch(false)->hideOnForm();
        yield TextField::new('billingCompanyName', 'Firmă (angro)')->hideOnIndex()->hideOnForm();
        yield TextField::new('couponCode', 'Cod cupon')->hideOnIndex()->hideOnForm();
        yield TextField::new('trackingNumber', 'AWB / tracking')->setRequired(false);
        // Adresa de livrare e editabilă liber pe o comandă (ex: client sună
        // să corecteze o greșeală înainte de expediere) — statusul/plata
        // trec doar prin acțiunile dedicate de mai sus.
        yield TextField::new('shippingFullName', 'Nume destinatar')->hideOnIndex();
        yield TextField::new('shippingPhone', 'Telefon')->hideOnIndex();
        yield TextField::new('shippingStreet', 'Stradă')->hideOnIndex();
        yield TextField::new('shippingCity', 'Localitate')->hideOnIndex();
        yield TextField::new('shippingCounty', 'Județ')->hideOnIndex();
        yield TextField::new('shippingPostalCode', 'Cod poștal')->hideOnIndex();
        yield TextareaField::new('adminNotes', 'Notițe interne')->setRequired(false)->hideOnIndex()->setHelp('Vizibile doar în admin — clientul nu le vede niciodată.');
        yield TextField::new('refundReason', 'Motiv rambursare')->hideOnIndex()->hideOnForm();
        yield DateTimeField::new('refundedAt', 'Rambursată la')->hideOnIndex()->hideOnForm();
        yield DateTimeField::new('deliveredAt', 'Livrată la')->hideOnIndex()->hideOnForm();
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
            ->add(BooleanFilter::new('isWholesaleOrder', 'Angro'))
            ->add(DateTimeFilter::new('createdAt', 'Data'))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // Statusul nu se editează liber dintr-un dropdown — doar prin
        // acțiuni dedicate, ca să nu poți pune o comandă într-o stare
        // inconsistentă din greșeală (ex: "livrată" fără să fi fost plătită).
        $markPaid = Action::new('markPaid', 'Marchează ca plătită', 'fa fa-money-bill')
            // linkToUrl (nu linkToCrudAction) ca să putem adăuga un token CSRF în
            // URL — acțiunea are efecte reale (plată, email) și nu trebuie
            // declanșabilă printr-un simplu GET forjat.
            ->linkToUrl(fn (Order $order) => $this->adminUrlGenerator
                ->unsetAll()
                ->setController(self::class)
                ->setAction('markPaid')
                ->setEntityId($order->getId())
                ->set('_token', $this->csrfTokenManager->getToken('mark_paid_'.$order->getId())->getValue())
                ->generateUrl())
            ->displayIf(static fn (Order $order) => PaymentStatus::Paid !== $order->getPaymentStatus())
            ->setCssClass('text-success')
        ;
        $markShipped = Action::new('markShipped', 'Marchează ca expediată', 'fa fa-truck')
            // linkToUrl (nu linkToCrudAction) ca să putem adăuga un token CSRF în
            // URL — acțiunea are efecte reale (schimbă statusul comenzii) și nu
            // trebuie declanșabilă printr-un simplu GET forjat.
            ->linkToUrl(fn (Order $order) => $this->adminUrlGenerator
                ->unsetAll()
                ->setController(self::class)
                ->setAction('markShipped')
                ->setEntityId($order->getId())
                ->set('_token', $this->csrfTokenManager->getToken('mark_shipped_'.$order->getId())->getValue())
                ->generateUrl())
            ->displayIf(static fn (Order $order) => !\in_array($order->getStatus(), [OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Cancelled], true))
        ;
        $markDelivered = Action::new('markDelivered', 'Marchează ca livrată', 'fa fa-box-open')
            ->linkToUrl(fn (Order $order) => $this->adminUrlGenerator
                ->unsetAll()
                ->setController(self::class)
                ->setAction('markDelivered')
                ->setEntityId($order->getId())
                ->set('_token', $this->csrfTokenManager->getToken('mark_delivered_'.$order->getId())->getValue())
                ->generateUrl())
            // Doar din „Expediată" — livrarea vine după expediere. Momentul
            // livrării pornește fereastra de retur de 14 zile (vezi ReturnRequest).
            ->displayIf(static fn (Order $order) => OrderStatus::Shipped === $order->getStatus())
        ;
        $invoice = Action::new('invoice', 'Factură (PDF)', 'fa fa-file-pdf')
            ->linkToRoute('app_order_invoice', static fn (Order $order) => ['id' => $order->getId()])
            ->setHtmlAttributes(['target' => '_blank'])
        ;
        $cancelOrder = Action::new('adminCancelOrder', 'Anulează / rambursează', 'fa fa-rotate-left')
            // linkToUrl (nu linkToCrudAction) ca să putem adăuga un token CSRF în
            // URL — acțiunea are efecte reale (rambursare, restoc, email) și nu
            // trebuie declanșabilă printr-un simplu GET forjat (ex: link/imagine
            // pe o pagină externă vizitată de un admin autentificat).
            ->linkToUrl(fn (Order $order) => $this->adminUrlGenerator
                ->unsetAll()
                ->setController(self::class)
                ->setAction('adminCancelOrder')
                ->setEntityId($order->getId())
                ->set('_token', $this->csrfTokenManager->getToken('admin_cancel_order_'.$order->getId())->getValue())
                ->generateUrl())
            ->displayIf(static fn (Order $order) => !\in_array($order->getStatus(), [OrderStatus::Cancelled, OrderStatus::Delivered], true))
            ->setCssClass('text-danger')
        ;
        $exportCsv = Action::new('exportCsv', 'Exportă CSV', 'fa fa-file-csv')
            ->linkToRoute('admin_order_export_csv')
            ->createAsGlobalAction()
        ;

        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, $markPaid)
            ->add(Crud::PAGE_INDEX, $markShipped)
            ->add(Crud::PAGE_INDEX, $markDelivered)
            ->add(Crud::PAGE_INDEX, $invoice)
            ->add(Crud::PAGE_INDEX, $cancelOrder)
            ->add(Crud::PAGE_INDEX, $exportCsv)
            ->add(Crud::PAGE_DETAIL, $markPaid)
            ->add(Crud::PAGE_DETAIL, $markShipped)
            ->add(Crud::PAGE_DETAIL, $markDelivered)
            ->add(Crud::PAGE_DETAIL, $invoice)
            ->add(Crud::PAGE_DETAIL, $cancelOrder)
            // Financiarul confirmă plata, Comenzi gestionează expedierea/livrarea — fiecare
            // vede/poate declanșa doar acțiunea din aria lui (ROLE_ADMIN le are pe amândouă).
            ->setPermission('markPaid', 'ROLE_FINANCE_MANAGER')
            ->setPermission('markShipped', 'ROLE_ORDERS_MANAGER')
            ->setPermission('markDelivered', 'ROLE_ORDERS_MANAGER')
            // Anularea/rambursarea și editarea adresei rămân doar la admin — au impact financiar/legal.
            ->setPermission('adminCancelOrder', 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
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
        if ($order && !$this->isCsrfTokenValid('mark_paid_'.$order->getId(), $request->query->get('_token'))) {
            $this->addFlash('danger', 'Token de securitate invalid sau expirat — reîncearcă din pagina comenzii.');

            return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($order->getId())->generateUrl());
        }
        if ($order && PaymentStatus::Paid !== $order->getPaymentStatus()) {
            $order->setPaymentStatus(PaymentStatus::Paid);
            if (OrderStatus::Pending === $order->getStatus()) {
                $order->setStatus(OrderStatus::Confirmed);
            }
            $entityManager->flush();

            $email = (new TemplatedEmail())
                ->from(new EmailAddress($store->email, $store->name))
                ->to($order->getContactEmail())
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
        if ($order && !$this->isCsrfTokenValid('mark_shipped_'.$order->getId(), $request->query->get('_token'))) {
            $this->addFlash('danger', 'Token de securitate invalid sau expirat — reîncearcă din pagina comenzii.');

            return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($order->getId())->generateUrl());
        }
        if ($order) {
            $order->setStatus(OrderStatus::Shipped);
            $entityManager->flush();
        }

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($order?->getId())->generateUrl());
    }

    #[AdminRoute(path: '/mark-delivered', name: 'mark_delivered')]
    #[IsGranted('ROLE_ORDERS_MANAGER')]
    public function markDelivered(Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        $order = $entityManager->getRepository(Order::class)->find($request->query->get('entityId'));
        if ($order && !$this->isCsrfTokenValid('mark_delivered_'.$order->getId(), $request->query->get('_token'))) {
            $this->addFlash('danger', 'Token de securitate invalid sau expirat — reîncearcă din pagina comenzii.');

            return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($order->getId())->generateUrl());
        }
        if ($order && OrderStatus::Shipped === $order->getStatus()) {
            $order->markDelivered();
            $entityManager->flush();
        }

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($order?->getId())->generateUrl());
    }

    #[AdminRoute(path: '/admin-cancel', name: 'admin_cancel_order')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminCancelOrder(Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator, OrderService $orderService): RedirectResponse
    {
        $order = $entityManager->getRepository(Order::class)->find($request->query->get('entityId'));
        if ($order && !$this->isCsrfTokenValid('admin_cancel_order_'.$order->getId(), $request->query->get('_token'))) {
            $this->addFlash('danger', 'Token de securitate invalid sau expirat — reîncearcă din pagina comenzii.');

            return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($order->getId())->generateUrl());
        }
        if ($order) {
            try {
                $orderService->adminCancelOrder($order, null);
                $this->addFlash('success', 'Comanda a fost anulată.');
            } catch (\DomainException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($order?->getId())->generateUrl());
    }

    #[Route('/admin/orders/export.csv', name: 'admin_order_export_csv')]
    #[IsGranted('ROLE_ADMIN')]
    public function exportCsv(OrderRepository $repository): StreamedResponse
    {
        $orders = $repository->findBy([], ['createdAt' => 'DESC']);

        $response = new StreamedResponse(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'data', 'client', 'status', 'metoda_plata', 'status_plata', 'total', 'transport', 'awb']);
            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->getId(),
                    $order->getCreatedAt()?->format('Y-m-d H:i:s'),
                    $order->getUser()?->getEmail(),
                    $order->getStatus()->value,
                    $order->getPaymentMethod()?->value,
                    $order->getPaymentStatus()->value,
                    $order->getTotal(),
                    $order->getShippingCost(),
                    $order->getTrackingNumber(),
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="comenzi.csv"');

        return $response;
    }
}
