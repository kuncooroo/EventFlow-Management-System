<?php

namespace App\Support\Demo;

use App\Models\Organization;

class DemoMode
{
    private static ?Organization $demoOrganization = null;

    private static bool $demoOrganizationResolved = false;

    public static function enabled(): bool
    {
        return (bool) config('demo.enabled', false);
    }

    public static function demoOrganizationSlug(): string
    {
        return (string) config('demo.organization_slug', 'demo-acme');
    }

    public static function demoOrganization(): ?Organization
    {
        if (self::$demoOrganizationResolved) {
            return self::$demoOrganization;
        }

        self::$demoOrganization = Organization::query()
            ->where('slug', self::demoOrganizationSlug())
            ->first();

        self::$demoOrganizationResolved = true;

        return self::$demoOrganization;
    }

    public static function demoOrganizationId(): ?int
    {
        return self::demoOrganization()?->id;
    }

    /**
     * Apply demo-mode side effects. Called from AppServiceProvider::boot() so
     * the demo instance can never deliver real mail to real attendees.
     */
    public static function apply(): void
    {
        if (! self::enabled()) {
            return;
        }

        config(['mail.default' => 'log']);
    }

    /**
     * Forget the cached demo organization. Tests toggle demo mode between
     * cases and must not leak a stale resolution across the same process.
     */
    public static function flushDemoOrganizationCache(): void
    {
        self::$demoOrganization = null;
        self::$demoOrganizationResolved = false;
    }
}
