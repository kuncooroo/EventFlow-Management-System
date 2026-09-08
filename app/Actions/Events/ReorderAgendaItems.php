<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReorderAgendaItems
{
    public function handle(Event $event, User $actor, array $orderedIds): void
    {
        Gate::forUser($actor)->authorize('update', $event);

        if ($event->status !== EventStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only Draft events can have agenda items managed.',
            ]);
        }

        $currentIds = $event->agendaItems()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $requested = array_values(array_map(fn ($id) => (int) $id, $orderedIds));

        $invalid = count($requested) !== count($currentIds)
            || $requested !== array_values(array_unique($requested))
            || array_diff($requested, $currentIds) !== []
            || array_diff($currentIds, $requested) !== [];

        if ($invalid) {
            throw ValidationException::withMessages([
                'order' => 'The agenda order must contain exactly the current agenda items.',
            ]);
        }

        foreach ($requested as $index => $id) {
            $event->agendaItems()->whereKey($id)->update(['sort_order' => $index]);
        }
    }
}
