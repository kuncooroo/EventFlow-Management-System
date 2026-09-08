<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateAgendaItem
{
    use ValidatesAgendaItem;

    public function handle(Event $event, User $actor, array $data): AgendaItem
    {
        Gate::forUser($actor)->authorize('update', $event);

        if ($event->status !== EventStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only Draft events can have agenda items managed.',
            ]);
        }

        $this->validateAgendaItem($data);

        $maxSortOrder = $event->agendaItems()->max('sort_order') ?? 0;

        return $event->agendaItems()->create([
            'title' => trim($data['title']),
            'description' => $this->normalizeNullable($data['description'] ?? null),
            'start_at' => $data['start_at'],
            'end_at' => $this->normalizeNullable($data['end_at'] ?? null),
            'location' => $this->normalizeNullable($data['location'] ?? null),
            'speaker_text' => $this->normalizeNullable($data['speaker_text'] ?? null),
            'sort_order' => $maxSortOrder + 1,
        ]);
    }
}
