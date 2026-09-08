<?php

namespace App\Actions\Events;

use App\Actions\ActivityLogs\RecordActivity;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MarkEventOngoing
{
    public function __construct(
        private readonly RecordActivity $recordActivity,
    ) {}

    public function handle(Event $event, User $actor): Event
    {
        Gate::forUser($actor)->authorize('markOngoing', $event);

        if ($event->status !== EventStatus::Published) {
            throw ValidationException::withMessages([
                'lifecycle' => 'Only a Published event can be started.',
            ]);
        }

        return DB::transaction(function () use ($event, $actor): Event {
            $event->update([
                'status' => EventStatus::Ongoing->value,
                'started_at' => now(),
            ]);

            $this->recordActivity->handle(
                organization: $event->organization,
                action: 'event.started',
                subjectType: 'event',
                subjectId: $event->id,
                actor: $actor,
                event: $event,
                summary: "Event '{$event->name}' started.",
                properties: [
                    'event_id' => $event->id,
                    'previous_status' => EventStatus::Published->value,
                    'new_status' => EventStatus::Ongoing->value,
                ],
            );

            return $event->refresh();
        });
    }
}
