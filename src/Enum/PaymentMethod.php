<?php

namespace App\Enum;

enum PaymentMethod: string
{
    case Card = 'card';
    case Cod = 'cod';
    case BankTransfer = 'bank_transfer';
}
