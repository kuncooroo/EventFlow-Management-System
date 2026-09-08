<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\AgendaItem;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateAgendaItem
{
    use ValidatesAgendaItem;

    public function handle(AgendaItem $item, User $actor, array $data): AgendaItem
    {
        Gate::forUser($actor)->authorize('update', $item->event);

        if ($item->event->status !== EventStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only Draft events can have agenda items managed.',
            ]);
        }

        $this->validateAgendaItem($data);

        $item->update([
            'title' => trim($data['title']),
            'description' => $this->normalizeNullable($data['description'] ?? null),
            'start_at' => $data['start_at'],
            'end_at' => $this->normalizeNullable($data['end_at'] ?? null),
            'location' => $this->normalizeNullable($data['location'] ?? null),
            'speaker_text' => $this->normalizeNullable($data['speaker_text'] ?? null),
        ]);

        return $item->refresh();
    }
}
