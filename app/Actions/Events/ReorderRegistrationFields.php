<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReorderRegistrationFields
{
    public function handle(Event $event, User $actor, array $orderedIds): void
    {
        Gate::forUser($actor)->authorize('update', $event);

        $currentIds = $event->registrationFields()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $requested = array_values(array_map(fn ($id) => (int) $id, $orderedIds));

        $invalid = count($requested) !== count($currentIds)
            || $requested !== array_values(array_unique($requested))
            || array_diff($requested, $currentIds) !== []
            || array_diff($currentIds, $requested) !== [];

        if ($invalid) {
            throw ValidationException::withMessages([
                'order' => 'The field order must contain exactly the current fields.',
            ]);
        }

        foreach ($requested as $index => $id) {
            $event->registrationFields()->whereKey($id)->update(['sort_order' => $index]);
        }
    }
}
