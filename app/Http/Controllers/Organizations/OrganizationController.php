<?php

namespace App\Http\Controllers\Organizations;

use App\Actions\Organizations\CreateOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Organizations\SwitchOrganizationRequest;
use App\Models\Organization;
use App\Support\Organization\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function create(Request $request): View
    {
        return view('pages.app.organizations.create', [
            'status' => session('status'),
        ]);
    }

    public function store(
        StoreOrganizationRequest $request,
        CreateOrganization $createOrganization,
    ): RedirectResponse {
        $this->authorize('create', Organization::class);

        $organization = $createOrganization->handle(
            $request->user(),
            $request->validated('name'),
        );

        return redirect()
            ->route('app.dashboard')
            ->with('status', __('Organization :name created successfully.', ['name' => $organization->name]));
    }

    public function switch(
        SwitchOrganizationRequest $request,
        OrganizationContext $organizationContext,
    ): RedirectResponse {
        $organization = Organization::query()->findOrFail($request->validated('organization_id'));

        $this->authorize('switch', $organization);

        $organizationContext->set($organization);

        return redirect()
            ->back()
            ->with('status', __('Switched to :name.', ['name' => $organization->name]));
    }
}
