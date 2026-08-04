<?php

namespace App\Service;

use App\Dto\CheckoutData;
use App\Entity\Address;
use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use App\Enum\StockStatus;
use App\Exception\InsufficientStockException;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;

final class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartManager $cartManager,
        private readonly AddressRepository $addressRepository,
        private readonly MailerInterface $mailer,
        private readonly StoreConfig $store,
        private readonly CampaignEngine $campaignEngine,
    ) {
    }

    /**
     * @throws InsufficientStockException
     */
    public function placeOrder(User $user, Cart $cart, CheckoutData $data, ?string $couponCode = null): Order
    {
        if ($cart->getItems()->isEmpty()) {
            throw new \DomainException('Coșul este gol.');
        }

        // Calculat o singură dată aici (nu recalculat în tranzacție), ca
        // rezultatul (inclusiv cadourile BOGO auto-incluse) să fie identic
        // cu ce a validat verificarea de stoc de mai jos.
        $campaignResult = $this->campaignEngine->applyCampaigns($cart, $couponCode);

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
            $order = (new Order())
                ->setUser($user)
                ->setShippingFullName($data->fullName)
                ->setShippingPhone($data->phone)
                ->setShippingCounty($data->county)
                ->setShippingCity($data->city)
                ->setShippingStreet($data->street)
                ->setShippingPostalCode($data->postalCode)
                ->setStatus(OrderStatus::Pending)
                ->setPaymentMethod($data->paymentMethod)
                ->setPaymentStatus(PaymentStatus::Pending)
                ->setTotal($campaignResult->total)
                ->setCouponCode($couponCode ?: null)
            ;

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
            $this->saveDefaultAddress($user, $data);
            $this->cartManager->clear($cart);

            $this->sendConfirmationEmail($order);

            return $order;
        });
    }

    private function saveDefaultAddress(User $user, CheckoutData $data): void
    {
        $address = $this->addressRepository->findOneBy(['user' => $user, 'isDefault' => true]) ?? (new Address())
            ->setUser($user)
            ->setIsDefault(true)
        ;

        $address
            ->setFullName($data->fullName)
            ->setPhone($data->phone)
            ->setCounty($data->county)
            ->setCity($data->city)
            ->setStreet($data->street)
            ->setPostalCode($data->postalCode)
        ;

        $this->entityManager->persist($address);
    }

    private function sendConfirmationEmail(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new EmailAddress($this->store->email, $this->store->name))
            ->to($order->getUser()->getEmail())
            ->subject(sprintf('Comanda ta la %s a fost înregistrată', $this->store->name))
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context(['order' => $order])
        ;

        $this->mailer->send($email);
    }
}
