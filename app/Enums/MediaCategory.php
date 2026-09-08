<?php

namespace App\Enums;

enum MediaCategory: string
{
    case OrganizationLogo = 'organization_logo';
    case EventBanner = 'event_banner';
    case EventSupporting = 'event_supporting';

    public function label(): string
    {
        return match ($this) {
            self::OrganizationLogo => 'Organization Logo',
            self::EventBanner => 'Event Banner',
            self::EventSupporting => 'Supporting Image',
        };
    }

    public function maxSizeBytes(): int
    {
        return 5 * 1024 * 1024;
    }

    /**
     * True when the asset is attached to an event rather than the organization alone.
     */
    public function isEventBound(): bool
    {
        return $this === self::EventBanner || $this === self::EventSupporting;
    }
}
