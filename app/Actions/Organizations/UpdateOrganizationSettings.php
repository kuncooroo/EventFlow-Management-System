<?php

namespace App\Actions\Organizations;

use App\Actions\ActivityLogs\RecordActivity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateOrganizationSettings
{
    public function __construct(
        private readonly RecordActivity $recordActivity,
    ) {}

    public function handle(Organization $organization, User $actor, array $data): Organization
    {
        Gate::forUser($actor)->authorize('manageSettings', $organization);

        $validated = validator($data, [
            'name' => ['required', 'string', 'max:150'],
            'timezone' => ['required', 'string', 'max:64', Rule::in(\DateTimeZone::listIdentifiers())],
            'locale' => ['required', 'string', Rule::in(config('app.supported_locales'))],
            'default_currency' => ['nullable', 'string', 'size:3', 'alpha'],
        ])->validate();

        $updates = [
            'name' => trim($validated['name']),
            'timezone' => $validated['timezone'],
            'locale' => $validated['locale'],
            'default_currency' => $validated['default_currency'] !== null
                ? Str::upper($validated['default_currency'])
                : null,
        ];

        return DB::transaction(function () use ($organization, $actor, $updates): Organization {
            $original = $organization->only(array_keys($updates));

            $organization->update($updates);

            $changed = array_keys(array_filter(
                $updates,
                fn ($value, string $key): bool => $original[$key] !== $value,
                ARRAY_FILTER_USE_BOTH,
            ));

            if ($changed !== []) {
                $this->recordActivity->handle(
                    organization: $organization,
                    action: 'organization.settings_changed',
                    subjectType: 'organization',
                    subjectId: $organization->id,
                    actor: $actor,
                    summary: 'Organization settings updated.',
                    properties: [
                        'organization_id' => $organization->id,
                        'changed_fields' => $changed,
                    ],
                );
            }

            return $organization->refresh();
        });
    }
}
