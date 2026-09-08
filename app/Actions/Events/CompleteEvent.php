<?php

namespace App\Actions\Events;

use App\Actions\ActivityLogs\RecordActivity;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CompleteEvent
{
    public function __construct(
        private readonly RecordActivity $recordActivity,
    ) {}

    public function handle(Event $event, User $actor): Event
    {
        Gate::forUser($actor)->authorize('completeEvent', $event);

        if ($event->status !== EventStatus::Ongoing) {
            throw ValidationException::withMessages([
                'lifecycle' => 'Only an Ongoing event can be completed.',
            ]);
        }

        return DB::transaction(function () use ($event, $actor): Event {
            $event->update([
                'status' => EventStatus::Completed->value,
                'completed_at' => now(),
            ]);

            $this->recordActivity->handle(
                organization: $event->organization,
                action: 'event.completed',
                subjectType: 'event',
                subjectId: $event->id,
                actor: $actor,
                event: $event,
                summary: "Event '{$event->name}' completed.",
                properties: [
                    'event_id' => $event->id,
                    'previous_status' => EventStatus::Ongoing->value,
                    'new_status' => EventStatus::Completed->value,
                ],
            );

            return $event->refresh();
        });
    }
}
