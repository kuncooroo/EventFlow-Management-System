<?php

namespace App\Enums;

enum VenueMode: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::Hybrid => 'Hybrid',
        };
    }

    public function requiresVenue(): bool
    {
        return $this !== self::Online;
    }
}
