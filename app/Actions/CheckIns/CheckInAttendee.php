<?php

namespace App\Actions\CheckIns;

use App\Actions\ActivityLogs\RecordActivity;
use App\Data\CheckIns\CheckInResult;
use App\Enums\CheckInMethod;
use App\Enums\RegistrationStatus;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CheckInAttendee
{
    public function __construct(
        private readonly RecordActivity $recordActivity,
    ) {}

    /**
     * Record a single immutable attendance row for a confirmed registration.
     *
     * Manual and QR flows share this action. Concurrency is guarded by the
     * registration row lock plus the check_ins.registration_id unique index.
     */
    public function handle(
        Event $event,
        Registration $registration,
        User $operator,
        CheckInMethod $method,
        ?Ticket $ticket = null
    ): CheckInResult {
        Gate::forUser($operator)->authorize('checkIn', [$registration, $event]);

        return DB::transaction(function () use ($event, $registration, $operator, $method, $ticket) {
            $locked = Registration::query()
                ->where('event_id', $event->id)
                ->lockForUpdate()
                ->findOrFail($registration->id);

            if ($locked->status !== RegistrationStatus::Confirmed) {
                return CheckInResult::invalid('This registration is not eligible for check-in.');
            }

            if ($ticket !== null && $ticket->registration_id !== $locked->id) {
                return CheckInResult::invalid('This ticket does not belong to the attendee registration.');
            }

            $existing = CheckIn::query()
                ->where('registration_id', $locked->id)
                ->first();

            if ($existing !== null) {
                return CheckInResult::duplicate($existing);
            }

            try {
                $checkIn = CheckIn::create([
                    'registration_id' => $locked->id,
                    'ticket_id' => $ticket?->id,
                    'operator_user_id' => $operator->id,
                    'method' => $method,
                    'checked_in_at' => now(),
                    'created_at' => now(),
                ]);
            } catch (QueryException $e) {
                if ($this->isUniqueViolation($e)) {
                    $previous = CheckIn::query()
                        ->where('registration_id', $locked->id)
                        ->first();

                    return $previous !== null
                        ? CheckInResult::duplicate($previous)
                        : CheckInResult::invalid('This registration is not eligible for check-in.');
                }

                throw $e;
            }

            $action = $method === CheckInMethod::Qr ? 'checkin.qr' : 'checkin.manual';

            $this->recordActivity->handle(
                organization: $event->organization,
                action: $action,
                subjectType: 'registration',
                subjectId: $locked->id,
                actor: $operator,
                event: $event,
                summary: "Attendee '{$locked->attendee_name}' checked in (".$method->value.').',
                properties: [
                    'registration_id' => $locked->id,
                    'ticket_id' => $ticket?->id,
                    'method' => $method->value,
                    'checked_in_at' => $checkIn->checked_in_at?->toIso8601String(),
                ],
            );

            return CheckInResult::success($checkIn);
        }, 5);
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'Duplicate entry')
            || str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || in_array((int) $e->errorInfo[1] ?? 0, [1062, 19, 2601, 2627], true);
    }
}
