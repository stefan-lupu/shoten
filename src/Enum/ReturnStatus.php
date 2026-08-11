<?php

namespace App\Enum;

enum ReturnStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
}
