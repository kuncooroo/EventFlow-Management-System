<?php

namespace App\Actions\Events;

use App\Actions\ActivityLogs\RecordActivity;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ArchiveEvent
{
    /**
     * Statuses from which archiving is a valid transition (BUSINESS_FLOW §4.1.1).
     */
    private const ARCHIVABLE_FROM = [
        EventStatus::Completed,
        EventStatus::Cancelled,
    ];

    public function __construct(
        private readonly RecordActivity $recordActivity,
    ) {}

    public function handle(Event $event, User $actor): Event
    {
        Gate::forUser($actor)->authorize('archive', $event);

        if (! in_array($event->status, self::ARCHIVABLE_FROM, true)) {
            throw ValidationException::withMessages([
                'lifecycle' => 'Only Completed or Cancelled events can be archived.',
            ]);
        }

        $previousStatus = $event->status;

        return DB::transaction(function () use ($event, $actor, $previousStatus): Event {
            $event->update([
                'status' => EventStatus::Archived->value,
                'archived_at' => now(),
            ]);

            $this->recordActivity->handle(
                organization: $event->organization,
                action: 'event.archived',
                subjectType: 'event',
                subjectId: $event->id,
                actor: $actor,
                event: $event,
                summary: "Event '{$event->name}' archived.",
                properties: [
                    'event_id' => $event->id,
                    'previous_status' => $previousStatus->value,
                    'new_status' => EventStatus::Archived->value,
                ],
            );

            return $event->refresh();
        });
    }
}
