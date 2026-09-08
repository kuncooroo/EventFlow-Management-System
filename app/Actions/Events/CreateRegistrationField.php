<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\RegistrationField;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateRegistrationField
{
    use ValidatesRegistrationField;

    public function handle(Event $event, User $actor, array $data): RegistrationField
    {
        Gate::forUser($actor)->authorize('update', $event);

        $this->validateRegistrationField($data);

        $maxSortOrder = $event->registrationFields()->max('sort_order') ?? 0;

        return $event->registrationFields()->create([
            'field_key' => $this->makeFieldKey($event, $data['label']),
            'label' => trim($data['label']),
            'field_type' => $data['field_type'],
            'options_json' => $this->normalizeOptions($data),
            'is_required' => (bool) ($data['is_required'] ?? false),
            'is_active' => true,
            'sort_order' => $maxSortOrder + 1,
        ]);
    }

    private function makeFieldKey(Event $event, string $label): string
    {
        $base = 'field_'.Str::slug($label, '_');
        $key = $base;
        $suffix = 2;

        while ($event->registrationFields()->where('field_key', $key)->exists()) {
            $key = $base.'_'.$suffix;
            $suffix++;
        }

        return $key;
    }
}
