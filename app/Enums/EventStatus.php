<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Ongoing => 'Ongoing',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Archived => 'Archived',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Published => 'teal',
            self::Ongoing => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'red',
            self::Archived => 'amber',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
