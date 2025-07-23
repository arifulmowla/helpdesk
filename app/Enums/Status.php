<?php
namespace App\Enums;

use App\Traits\EnumHelper;

enum Status: string
{
    use EnumHelper;
    case OPEN = 'open';
    case CLOSED = 'closed';
    case AWAITING_CUSTOMER = 'awaiting_customer';
    case AWAITING_AGENT = 'awaiting_agent';
    case RESOLVED = 'resolved';
    case CANCELLED = 'cancelled';

    public function name(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::CLOSED => 'Closed',
            self::AWAITING_CUSTOMER => 'Awaiting Customer',
            self::AWAITING_AGENT => 'Awaiting Agent',
            self::RESOLVED => 'Resolved',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::OPEN => 'bg-green-100 text-green-800 border-green-200',
            self::CLOSED => 'bg-gray-100 text-gray-800 border-gray-200',
            self::AWAITING_CUSTOMER => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            self::AWAITING_AGENT => 'bg-blue-100 text-blue-800 border-blue-200',
            self::RESOLVED => 'bg-purple-100 text-purple-800 border-purple-200',
            self::CANCELLED => 'bg-red-100 text-red-800 border-red-200',
        };
    }
}
