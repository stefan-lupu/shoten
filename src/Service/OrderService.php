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
    ) {
    }

    /**
     * @throws InsufficientStockException
     */
    public function placeOrder(User $user, Cart $cart, CheckoutData $data): Order
    {
        if ($cart->getItems()->isEmpty()) {
            throw new \DomainException('Coșul este gol.');
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

        return $this->entityManager->wrapInTransaction(function () use ($user, $cart, $data) {
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
                ->setTotal($this->cartManager->getTotal($cart))
            ;

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
