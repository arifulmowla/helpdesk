<?php
namespace App\Enums;

enum Status: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case AWAITING_CUSTOMER = 'awaiting_customer';
    case AWAITING_AGENT = 'awaiting_agent';
    case RESOLVED = 'resolved';
    case CANCELLED = 'cancelled';
}
