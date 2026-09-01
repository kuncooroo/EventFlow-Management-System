<?php

namespace App\Providers;

use App\Support\Organization\OrganizationContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        Password::defaults(fn () => Password::min(8));

        View::composer('components.layouts.app', function ($view): void {
            $user = Auth::user();

            if ($user === null) {
                return;
            }

            $organizationContext = app(OrganizationContext::class);
            $accessibleOrganizations = $organizationContext->accessibleOrganizations($user);
            $currentOrganization = $organizationContext->current();

            if ($currentOrganization !== null && ! $accessibleOrganizations->contains('id', $currentOrganization->id)) {
                $currentOrganization = null;
            }

            $currentMembership = $currentOrganization !== null
                ? $user->membershipIn($currentOrganization)
                : null;

            $view->with('accessibleOrganizations', $accessibleOrganizations);
            $view->with('currentOrganization', $currentOrganization);
            $view->with('currentMembership', $currentMembership);
        });
    }
}
