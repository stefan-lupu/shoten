<?php

namespace App\Dto;

use App\Entity\Address;
use App\Enum\PaymentMethod;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sursa datelor de checkout, unificată pentru ambele fluxuri:
 * - cu cont: `address` e o adresă salvată aleasă din selector;
 * - guest: câmpurile brute (fullName..postalCode) + guestEmail sunt
 *   completate direct în formular.
 *
 * `placeOrder` citește snapshot-ul de livrare prin metodele shipping*()
 * de mai jos, care iau din `address` dacă e setată, altfel din câmpurile
 * brute — deci logica de plasare nu trebuie să știe care flux a fost.
 * Validarea per-câmp e pusă în form types (CheckoutType / GuestCheckoutType),
 * nu aici, fiindcă cele două fluxuri cer câmpuri diferite.
 */
class CheckoutData
{
    public ?Address $address = null;

    public ?string $fullName = null;
    public ?string $phone = null;
    public ?string $county = null;
    public ?string $city = null;
    public ?string $street = null;
    public ?string $postalCode = null;

    public ?string $guestEmail = null;

    #[Assert\NotNull(message: 'Alege o metodă de plată.')]
    public ?PaymentMethod $paymentMethod = null;

    public function shippingFullName(): ?string
    {
        return $this->address?->getFullName() ?? $this->fullName;
    }

    public function shippingPhone(): ?string
    {
        return $this->address?->getPhone() ?? $this->phone;
    }

    public function shippingCounty(): ?string
    {
        return $this->address?->getCounty() ?? $this->county;
    }

    public function shippingCity(): ?string
    {
        return $this->address?->getCity() ?? $this->city;
    }

    public function shippingStreet(): ?string
    {
        return $this->address?->getStreet() ?? $this->street;
    }

    public function shippingPostalCode(): ?string
    {
        return $this->address?->getPostalCode() ?? $this->postalCode;
    }
}
