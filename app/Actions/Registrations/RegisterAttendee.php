<?php

namespace App\Actions\Registrations;

use App\Actions\ActivityLogs\RecordActivity;
use App\Actions\Tickets\IssueTicket;
use App\Enums\EventStatus;
use App\Enums\RegistrationFieldType;
use App\Enums\RegistrationStatus;
use App\Events\RegistrationConfirmed;
use App\Models\Event;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\TicketType;
use App\Services\Registrations\RegistrationCapacityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterAttendee
{
    public function __construct(
        private readonly RegistrationCapacityService $capacity,
        private readonly RecordActivity $recordActivity,
    ) {}

    /**
     * Register an attendee inside a single capacity-safe transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Event $event, array $data): Registration
    {
        return DB::transaction(function () use ($event, $data) {
            // 1. Lock event row
            $lockedEvent = Event::query()
                ->whereKey($event->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Validate event is registration-eligible
            $this->assertEventEligible($lockedEvent);

            // 3. Lock selected ticket_type row if applicable
            $ticketType = null;
            $ticketTypeId = $data['ticket_type_id'] ?? null;

            if ($ticketTypeId !== null) {
                $ticketType = TicketType::query()
                    ->where('event_id', $lockedEvent->id)
                    ->whereKey($ticketTypeId)
                    ->lockForUpdate()
                    ->first();

                if ($ticketType === null || ! $ticketType->is_active) {
                    throw ValidationException::withMessages([
                        'ticket_type_id' => 'The selected ticket type is no longer available.',
                    ]);
                }

                // 4. Re-check ticket availability window
                $this->assertTicketTypeAvailable($ticketType);
            }

            // 5. Count capacity-consuming registrations
            // 6. Reject if event/ticket capacity exhausted
            $this->assertCapacityAvailable($lockedEvent, $ticketType);

            // 7. Insert registration
            $registration = Registration::create([
                'event_id' => $lockedEvent->id,
                'ticket_type_id' => $ticketType?->id,
                'registration_code' => bin2hex(random_bytes(13)),
                'status' => RegistrationStatus::Confirmed,
                'attendee_name' => $data['attendee_name'],
                'attendee_email' => $data['attendee_email'],
                'attendee_phone' => $data['attendee_phone'] ?? null,
                'attendee_organization' => $data['attendee_organization'] ?? null,
                'registered_at' => now(),
            ]);

            // 8. Insert registration answers
            $this->storeAnswers($registration, $data['answers'] ?? [], $lockedEvent);

            // 9. Insert ticket if required
            if ($ticketType !== null) {
                app(IssueTicket::class)->handle($registration);
            }

            // 10. Dispatch after commit
            DB::afterCommit(function () use ($registration) {
                RegistrationConfirmed::dispatch($registration);
            });

            return $registration;
        });
    }

    private function assertEventEligible(Event $event): void
    {
        if (in_array($event->status, [EventStatus::Draft, EventStatus::Cancelled, EventStatus::Archived], true)) {
            throw ValidationException::withMessages([
                'event' => 'Registration is not available for this event.',
            ]);
        }

        if (! $event->registration_enabled) {
            throw ValidationException::withMessages([
                'event' => 'Registration is disabled for this event.',
            ]);
        }

        $now = now();

        if ($event->registration_starts_at !== null && $now->lt($event->registration_starts_at)) {
            throw ValidationException::withMessages([
                'event' => 'Registration has not started yet.',
            ]);
        }

        if ($event->registration_ends_at !== null && $now->gt($event->registration_ends_at)) {
            throw ValidationException::withMessages([
                'event' => 'Registration has ended.',
            ]);
        }
    }

    private function assertTicketTypeAvailable(TicketType $ticketType): void
    {
        $now = now();

        if ($ticketType->available_from !== null && $now->lt($ticketType->available_from)) {
            throw ValidationException::withMessages([
                'ticket_type_id' => 'This ticket type is not available yet.',
            ]);
        }

        if ($ticketType->available_until !== null && $now->gt($ticketType->available_until)) {
            throw ValidationException::withMessages([
                'ticket_type_id' => 'This ticket type is no longer available.',
            ]);
        }
    }

    private function assertCapacityAvailable(Event $event, ?TicketType $ticketType): void
    {
        if ($this->capacity->isEventSoldOut($event)) {
            throw ValidationException::withMessages([
                'event' => 'This event is sold out.',
            ]);
        }

        if ($ticketType !== null && $this->capacity->isTicketTypeSoldOut($event, $ticketType)) {
            throw ValidationException::withMessages([
                'ticket_type_id' => 'This ticket type is sold out.',
            ]);
        }
    }

    /**
     * Store registration answers, snapping label and type from active fields.
     *
     * @param  array<string, mixed>  $answers  key = registration_field_id, value = string|array
     */
    private function storeAnswers(Registration $registration, array $answers, Event $event): void
    {
        $fields = RegistrationField::query()
            ->where('event_id', $event->id)
            ->active()
            ->get()
            ->keyBy('id');

        foreach ($answers as $fieldId => $value) {
            $field = $fields->get((int) $fieldId);

            if ($field === null) {
                continue;
            }

            $isCheckbox = $field->field_type === RegistrationFieldType::Checkbox;

            $registration->answers()->create([
                'registration_field_id' => $field->id,
                'field_label_snapshot' => $field->label,
                'field_type_snapshot' => $field->field_type->value,
                'answer_text' => $isCheckbox ? null : (is_string($value) ? trim($value) : null),
                'answer_json' => $isCheckbox ? (is_array($value) ? $value : null) : null,
            ]);
        }
    }
}
