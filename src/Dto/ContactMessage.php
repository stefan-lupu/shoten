<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ContactMessage
{
    #[Assert\NotBlank(message: 'Introdu numele tău.')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Introdu adresa de email.')]
    #[Assert\Email(message: 'Adresa de email nu este validă.')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Introdu subiectul.')]
    public ?string $subject = null;

    #[Assert\NotBlank(message: 'Introdu mesajul tău.')]
    #[Assert\Length(max: 5000, maxMessage: 'Mesajul este prea lung.')]
    public ?string $message = null;
}
