<?php
namespace App\Enums;

use App\Traits\EnumHelper;

enum Type: string
{
    use EnumHelper;
    case AGENT = 'agent';
    case CUSTOMER = 'customer';
    case INTERNAL = 'internal';
}
