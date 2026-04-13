<?php

namespace App\Enums;

enum TicketStatus: string
{
    case OPEN = 'open';
    case ASSIGNED = 'assigned';
    case CLOSED = 'closed';

    public static function values(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }

    public static function labels(): array
    {
        return [
            self::OPEN->value => 'Open',
            self::ASSIGNED->value => 'In Progress',
            self::CLOSED->value => 'Closed',
        ];
    }
}
