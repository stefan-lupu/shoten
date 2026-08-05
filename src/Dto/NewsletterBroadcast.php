<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class NewsletterBroadcast
{
    #[Assert\NotBlank(message: 'Introdu subiectul.')]
    public ?string $subject = null;

    #[Assert\NotBlank(message: 'Introdu conținutul.')]
    public ?string $body = null;
}
