<?php

namespace App\Enums;

enum TicketPriority: string
{
    case LOW = 'Low';
    case MEDIUM = 'Medium';
    case HIGH = 'High';

    public static function values(): array
    {
        return array_map(static fn (self $priority) => $priority->value, self::cases());
    }

    public static function labels(): array
    {
        return [
            self::LOW->value => 'Low',
            self::MEDIUM->value => 'Medium',
            self::HIGH->value => 'High',
        ];
    }
}
