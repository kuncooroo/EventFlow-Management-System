<?php

namespace App\Http\Middleware;

use App\Support\Organization\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationContext
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $accessibleOrganizations = $this->organizationContext->accessibleOrganizations($user);

        if ($accessibleOrganizations->isEmpty()) {
            return redirect()->route('app.organizations.create');
        }

        $currentOrganization = $this->organizationContext->resolveForUser($user);

        if ($currentOrganization === null) {
            return redirect()->route('app.organizations.create');
        }

        View::share('currentOrganization', $currentOrganization);
        View::share('accessibleOrganizations', $accessibleOrganizations);

        return $next($request);
    }
}
