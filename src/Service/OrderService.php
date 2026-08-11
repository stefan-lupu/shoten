<?php

namespace App\Service;

use App\Dto\CheckoutData;
use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use App\Enum\StockStatus;
use App\Exception\InsufficientStockException;
use App\Exception\WholesaleMinimumNotMetException;
use App\Repository\ShippingSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

final class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartManager $cartManager,
        private readonly MailerInterface $mailer,
        private readonly StoreConfig $store,
        private readonly CampaignEngine $campaignEngine,
        private readonly RoleHierarchyInterface $roleHierarchy,
        private readonly ShippingSettingsRepository $shippingSettingsRepository,
    ) {
    }

    /**
     * @throws InsufficientStockException
     */
    public function placeOrder(?User $user, Cart $cart, CheckoutData $data, ?string $couponCode = null): Order
    {
        if ($cart->getItems()->isEmpty()) {
            throw new \DomainException('Coșul este gol.');
        }

        // Calculat o singură dată aici (nu recalculat în tranzacție), ca
        // rezultatul (inclusiv cadourile BOGO auto-incluse) să fie identic
        // cu ce a validat verificarea de stoc de mai jos.
        $campaignResult = $this->campaignEngine->applyCampaigns($cart, $couponCode);

        $minimumError = $this->wholesaleMinimumError($user, $cart, $campaignResult->subtotal);
        if (null !== $minimumError) {
            throw new WholesaleMinimumNotMetException($minimumError);
        }

        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();
            if ($product->getStockStatus() === StockStatus::InStock && $item->getQuantity() > $product->getStock()) {
                throw new InsufficientStockException(sprintf(
                    'Stoc insuficient pentru „%s”. Disponibil: %d buc.',
                    $product->getName(),
                    $product->getStock(),
                ));
            }
        }

        foreach ($campaignResult->discounts as $discount) {
            $giftProduct = $discount->freeGiftProduct;
            if ($giftProduct && $discount->freeGiftQuantity > 0
                && $giftProduct->getStockStatus() === StockStatus::InStock
                && $discount->freeGiftQuantity > $giftProduct->getStock()
            ) {
                throw new InsufficientStockException(sprintf(
                    'Stoc insuficient pentru cadoul „%s” din campania „%s”. Disponibil: %d buc.',
                    $giftProduct->getName(),
                    $discount->campaign->getName(),
                    $giftProduct->getStock(),
                ));
            }
        }

        return $this->entityManager->wrapInTransaction(function () use ($user, $cart, $data, $couponCode, $campaignResult) {
            // Snapshot al adresei la momentul comenzii — dacă userul o editează
            // sau o șterge ulterior, istoricul comenzii rămâne neschimbat.
            $order = (new Order())
                ->setUser($user)
                ->setShippingFullName($data->shippingFullName())
                ->setShippingPhone($data->shippingPhone())
                ->setShippingCounty($data->shippingCounty())
                ->setShippingCity($data->shippingCity())
                ->setShippingStreet($data->shippingStreet())
                ->setShippingPostalCode($data->shippingPostalCode())
                ->setStatus(OrderStatus::Pending)
                ->setPaymentMethod($data->paymentMethod)
                ->setPaymentStatus(PaymentStatus::Pending)
                ->setTotal($campaignResult->total)
                ->setShippingCost($campaignResult->shippingCost)
                ->setCouponCode($couponCode ?: null)
            ;

            // Comandă guest (fără cont): reținem emailul de contact și un token
            // aleator pentru pagina publică de confirmare (guestul nu poate
            // accesa /cont ca să-și vadă comanda).
            if (null === $user) {
                $order->setGuestEmail($data->guestEmail);
                $order->setGuestToken(bin2hex(random_bytes(16)));
            }

            // Snapshot al datelor firmei la momentul comenzii, la fel ca
            // adresa de livrare de mai sus — vezi tasks/17-checkout-facturare-angro.md.
            // Verificăm prin RoleHierarchyInterface (nu User::getRoles() direct),
            // ca un ROLE_ADMIN care moștenește ROLE_WHOLESALE să fie tratat identic
            // cu un cont angro aprobat (aceeași convenție ca WholesalePricingResolver).
            if (null !== $user && \in_array('ROLE_WHOLESALE', $this->roleHierarchy->getReachableRoleNames($user->getRoles()), true)) {
                $order
                    ->setIsWholesaleOrder(true)
                    ->setBillingCompanyName($user->getCompanyName())
                    ->setBillingCompanyCui($user->getCompanyCui())
                    ->setBillingCompanyRegCom($user->getCompanyRegCom())
                    ->setBillingCompanyAddress($user->getCompanyAddress())
                ;
            }

            foreach ($campaignResult->discounts as $discount) {
                $discount->campaign->incrementUsesCount();
            }

            foreach ($cart->getItems() as $cartItem) {
                $product = $cartItem->getProduct();

                $orderItem = (new OrderItem())
                    ->setProduct($product)
                    ->setProductName($product->getName())
                    ->setQuantity($cartItem->getQuantity())
                    ->setUnitPrice($cartItem->getUnitPrice())
                ;
                $order->addItem($orderItem);

                if ($product->getStockStatus() === StockStatus::InStock) {
                    $product->setStock($product->getStock() - $cartItem->getQuantity());
                }
            }

            foreach ($campaignResult->discounts as $discount) {
                $giftProduct = $discount->freeGiftProduct;
                if (!$giftProduct || $discount->freeGiftQuantity <= 0) {
                    continue;
                }

                $giftOrderItem = (new OrderItem())
                    ->setProduct($giftProduct)
                    ->setProductName($giftProduct->getName().' (cadou)')
                    ->setQuantity($discount->freeGiftQuantity)
                    ->setUnitPrice('0.00')
                ;
                $order->addItem($giftOrderItem);

                if ($giftProduct->getStockStatus() === StockStatus::InStock) {
                    $giftProduct->setStock($giftProduct->getStock() - $discount->freeGiftQuantity);
                }
            }

            $this->entityManager->persist($order);
            $this->cartManager->clear($cart);

            $this->sendConfirmationEmail($order);

            return $order;
        });
    }

    /**
     * Verifică pragurile minime (valoare și/sau bucăți) care se aplică DOAR
     * comenzilor angro — un client retail nu e afectat niciodată. Valoarea
     * comparată e subtotalul (valoarea produselor, fără transport). Praguri
     * null în ShippingSettings = fără restricție.
     *
     * Întoarce mesajul de eroare (pentru afișare) dacă un prag nu e atins,
     * sau null dacă totul e în regulă. Metodă publică refolosită și de
     * CartController pentru un mesaj informativ înainte de checkout.
     */
    public function wholesaleMinimumError(?User $user, Cart $cart, string $subtotal): ?string
    {
        // Comenzile guest nu sunt niciodată angro — nu au cont, deci nici rol.
        if (null === $user || !\in_array('ROLE_WHOLESALE', $this->roleHierarchy->getReachableRoleNames($user->getRoles()), true)) {
            return null;
        }

        $settings = $this->shippingSettingsRepository->getSettings();

        $minValue = $settings->getWholesaleMinOrderValue();
        if (null !== $minValue && bccomp($subtotal, $minValue, 2) < 0) {
            return sprintf(
                'Comanda minimă pentru conturi angro este de %s lei. Subtotalul tău este %s lei.',
                $minValue,
                $subtotal,
            );
        }

        $minItems = $settings->getWholesaleMinOrderItems();
        if (null !== $minItems) {
            $totalItems = 0;
            foreach ($cart->getItems() as $item) {
                $totalItems += $item->getQuantity();
            }
            if ($totalItems < $minItems) {
                return sprintf(
                    'Comanda minimă pentru conturi angro este de %d bucăți. În coș ai %d.',
                    $minItems,
                    $totalItems,
                );
            }
        }

        return null;
    }

    /**
     * @throws \DomainException dacă statusul nu (mai) permite anularea
     */
    public function cancelOrder(Order $order): void
    {
        if (OrderStatus::Pending !== $order->getStatus()) {
            throw new \DomainException('Doar comenzile în așteptare pot fi anulate.');
        }

        $this->entityManager->wrapInTransaction(function () use ($order) {
            foreach ($order->getItems() as $item) {
                $product = $item->getProduct();
                if ($product && $product->getStockStatus() === StockStatus::InStock) {
                    $product->setStock($product->getStock() + $item->getQuantity());
                }
            }

            $order->setStatus(OrderStatus::Cancelled);
        });
    }

    /**
     * Anulare/rambursare din admin — spre deosebire de cancelOrder() (client,
     * doar Pending), aici e permisă orice comandă neterminată, iar dacă era
     * deja plătită, se marchează explicit rambursată (evidență, nu declanșează
     * o rambursare reală la provider — Netopia e simulat, vezi tasks/done/07-plati.md).
     *
     * @throws \DomainException dacă statusul nu (mai) permite anularea
     */
    public function adminCancelOrder(Order $order, ?string $reason): void
    {
        if (\in_array($order->getStatus(), [OrderStatus::Cancelled, OrderStatus::Delivered], true)) {
            throw new \DomainException('Această comandă nu mai poate fi anulată.');
        }

        $wasPaid = PaymentStatus::Paid === $order->getPaymentStatus();

        $this->entityManager->wrapInTransaction(function () use ($order, $reason, $wasPaid) {
            foreach ($order->getItems() as $item) {
                $product = $item->getProduct();
                if ($product && $product->getStockStatus() === StockStatus::InStock) {
                    $product->setStock($product->getStock() + $item->getQuantity());
                }
            }

            $order->setStatus(OrderStatus::Cancelled);
            if ($wasPaid) {
                $order->setPaymentStatus(PaymentStatus::Refunded);
                $order->markRefunded($reason);
            }
        });

        $this->sendCancellationEmail($order, $wasPaid);
    }

    private function sendCancellationEmail(Order $order, bool $wasRefunded): void
    {
        $email = (new TemplatedEmail())
            ->from(new EmailAddress($this->store->email, $this->store->name))
            ->to($order->getContactEmail())
            ->subject(sprintf('Comanda #%d a fost anulată', $order->getId()))
            ->htmlTemplate('emails/order_cancelled.html.twig')
            ->context(['order' => $order, 'wasRefunded' => $wasRefunded])
        ;

        $this->mailer->send($email);
    }

    private function sendConfirmationEmail(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new EmailAddress($this->store->email, $this->store->name))
            ->to($order->getContactEmail())
            ->subject(sprintf('Comanda ta la %s a fost înregistrată', $this->store->name))
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context(['order' => $order])
        ;

        $this->mailer->send($email);
    }
}
