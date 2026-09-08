<?php

namespace App\Support\Demo;

use Illuminate\Auth\Access\AuthorizationException;

class DemoGate
{
    public static function deny(string $message): never
    {
        throw new AuthorizationException($message);
    }

    /**
     * Block a destructive action when demo mode is on and the affected data
     * belongs to the seeded demo organization. Non-demo organizations keep
     * every capability even while demo mode is enabled.
     */
    public static function denyOnDemoOrganization(int $organizationId, string $message): void
    {
        if (DemoMode::enabled() && DemoMode::demoOrganizationId() === $organizationId) {
            self::deny($message);
        }
    }
}
