<?php

namespace App\Actions\Events;

use App\Actions\ActivityLogs\RecordActivity;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CancelEvent
{
    /**
     * Statuses from which cancellation is a valid transition (BUSINESS_FLOW §4.1.1).
     */
    private const CANCELLABLE_FROM = [
        EventStatus::Draft,
        EventStatus::Published,
        EventStatus::Ongoing,
    ];

    public function __construct(
        private readonly RecordActivity $recordActivity,
    ) {}

    public function handle(Event $event, User $actor): Event
    {
        Gate::forUser($actor)->authorize('cancelEvent', $event);

        if (! in_array($event->status, self::CANCELLABLE_FROM, true)) {
            throw ValidationException::withMessages([
                'lifecycle' => 'Only Draft, Published, or Ongoing events can be cancelled.',
            ]);
        }

        $previousStatus = $event->status;

        return DB::transaction(function () use ($event, $actor, $previousStatus): Event {
            $event->update([
                'status' => EventStatus::Cancelled->value,
                'cancelled_at' => now(),
            ]);

            $this->recordActivity->handle(
                organization: $event->organization,
                action: 'event.cancelled',
                subjectType: 'event',
                subjectId: $event->id,
                actor: $actor,
                event: $event,
                summary: "Event '{$event->name}' cancelled.",
                properties: [
                    'event_id' => $event->id,
                    'previous_status' => $previousStatus->value,
                    'new_status' => EventStatus::Cancelled->value,
                ],
            );

            return $event->refresh();
        });
    }
}
