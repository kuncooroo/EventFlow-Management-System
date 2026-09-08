<?php

namespace App\Livewire\Organizations;

use App\Actions\Organizations\UpdateOrganizationSettings;
use App\Models\Organization;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Settings')]
class SettingsForm extends Component
{
    use AuthorizesRequests;

    public Organization $organization;

    public string $name = '';

    public string $timezone = '';

    public string $locale = '';

    public string $default_currency = '';

    public function mount(OrganizationContext $context): void
    {
        $user = auth()->user();
        $organization = $context->resolveForUser($user);

        if ($organization === null) {
            abort(403);
        }

        $this->organization = $organization;
        $this->authorize('manageSettings', $this->organization);

        $this->name = $this->organization->name;
        $this->timezone = $this->organization->timezone;
        $this->locale = $this->organization->locale;
        $this->default_currency = $this->organization->default_currency ?? '';
    }

    public function save(UpdateOrganizationSettings $action): void
    {
        Gate::authorize('manageSettings', $this->organization);

        $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'timezone' => ['required', 'string', 'max:64', Rule::in(timezone_identifiers_list())],
            'locale' => ['required', 'string', Rule::in(config('app.supported_locales'))],
            'default_currency' => ['nullable', 'string', 'size:3', 'alpha'],
        ]);

        try {
            $this->organization = $action->handle($this->organization, auth()->user(), [
                'name' => $this->name,
                'timezone' => $this->timezone,
                'locale' => $this->locale,
                'default_currency' => filled($this->default_currency) ? $this->default_currency : null,
            ]);

            session()->flash('status', 'Organization settings saved.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    public function render(): View
    {
        return view('livewire.organizations.settings-form', [
            'timezones' => timezone_identifiers_list(),
            'supportedLocales' => config('app.supported_locales'),
            'logo' => $this->organization->activeLogo(),
        ]);
    }
}
