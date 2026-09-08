<?php

namespace App\Actions\Events;

use App\Models\RegistrationField;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateRegistrationField
{
    use ValidatesRegistrationField;

    public function handle(RegistrationField $field, User $actor, array $data): RegistrationField
    {
        Gate::forUser($actor)->authorize('update', $field->event);

        $this->validateRegistrationField($data);

        $field->update([
            'label' => trim($data['label']),
            'field_type' => $data['field_type'],
            'options_json' => $this->normalizeOptions($data),
            'is_required' => (bool) ($data['is_required'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? $field->is_active),
        ]);

        return $field->refresh();
    }
}
