<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case EventManager = 'event_manager';
    case Staff = 'staff';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::EventManager => 'Event Manager',
            self::Staff => 'Staff',
            self::Viewer => 'Viewer',
        };
    }

    public function canManageMembers(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            default => false,
        };
    }

    public function canViewMembers(): bool
    {
        return match ($this) {
            self::Owner, self::Admin, self::EventManager => true,
            default => false,
        };
    }

    /**
     * @return list<self>
     */
    public static function assignable(): array
    {
        return self::cases();
    }
}
