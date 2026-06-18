<?php

namespace App\Enums;

enum UserStatus: string
{
    case Available = 'available';
    case Busy      = 'busy';
    case Away      = 'away';
    case Dnd       = 'dnd';

    public function cssClass(): string
    {
        return match($this) {
            self::Available => 'status-icon-online',
            self::Busy      => 'status-icon-busy',
            self::Away      => 'status-icon-away',
            self::Dnd       => 'status-icon-dnd',
        };
    }

    public function label(): string
    {
        return match($this) {
            self::Available => 'Available',
            self::Busy      => 'Busy',
            self::Away      => 'Away',
            self::Dnd       => 'Do Not Disturb',
        };
    }
}
