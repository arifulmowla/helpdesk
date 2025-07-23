<?php
namespace App\Enums;

use App\Traits\EnumHelper;

enum Priority: string
{
    use EnumHelper;
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function name(): string
    {
        return match($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::LOW => 'bg-blue-100 text-blue-800 border-blue-200',
            self::MEDIUM => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            self::HIGH => 'bg-orange-100 text-orange-800 border-orange-200',
            self::URGENT => 'bg-red-100 text-red-800 border-red-200',
        };
    }
}
