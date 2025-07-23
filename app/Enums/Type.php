<?php
namespace App\Enums;

use App\Traits\EnumHelper;

enum Type: string
{
    use EnumHelper;
    case AGENT = 'agent';
    case CUSTOMER = 'customer';

    public function name(): string
    {
        return match($this) {
            self::AGENT => 'Agent',
            self::CUSTOMER => 'Customer',
        };
    }
}
