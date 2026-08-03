<?php

namespace App\Dto;

use App\Entity\Address;
use App\Enum\PaymentMethod;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutData
{
    #[Assert\NotBlank(message: 'Introdu numele complet.')]
    public ?string $fullName = null;

    #[Assert\NotBlank(message: 'Introdu telefonul.')]
    public ?string $phone = null;

    #[Assert\NotBlank(message: 'Introdu județul.')]
    public ?string $county = null;

    #[Assert\NotBlank(message: 'Introdu localitatea.')]
    public ?string $city = null;

    #[Assert\NotBlank(message: 'Introdu strada și numărul.')]
    public ?string $street = null;

    #[Assert\NotBlank(message: 'Introdu codul poștal.')]
    public ?string $postalCode = null;

    #[Assert\NotNull(message: 'Alege o metodă de plată.')]
    public ?PaymentMethod $paymentMethod = null;

    public static function fromAddress(?Address $address): self
    {
        $data = new self();
        if ($address) {
            $data->fullName = $address->getFullName();
            $data->phone = $address->getPhone();
            $data->county = $address->getCounty();
            $data->city = $address->getCity();
            $data->street = $address->getStreet();
            $data->postalCode = $address->getPostalCode();
        }

        return $data;
    }
}
