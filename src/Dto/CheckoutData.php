<?php

namespace App\Dto;

use App\Entity\Address;
use App\Enum\PaymentMethod;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutData
{
    #[Assert\NotNull(message: 'Alege o adresă de livrare.')]
    public ?Address $address = null;

    #[Assert\NotNull(message: 'Alege o metodă de plată.')]
    public ?PaymentMethod $paymentMethod = null;
}
