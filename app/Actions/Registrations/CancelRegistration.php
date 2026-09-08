<?php

namespace App\Actions\Registrations;

use App\Actions\ActivityLogs\RecordActivity;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CancelRegistration
{
    public function __construct(
        private readonly RecordActivity $recordActivity,
    ) {}

    public function handle(Event $event, Registration $registration, User $actor, ?string $reason = null): Registration
    {
        Gate::forUser($actor)->authorize('cancel', [$registration, $event]);

        return DB::transaction(function () use ($event, $registration, $actor, $reason) {
            // Lock the registration row so concurrent cancellation revalidates latest state.
            $locked = Registration::query()
                ->where('event_id', $event->id)
                ->lockForUpdate()
                ->findOrFail($registration->id);

            if ($locked->status !== RegistrationStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'registration' => 'Only confirmed registrations can be cancelled.',
                ]);
            }

            if ($this->isCheckedIn($locked)) {
                throw ValidationException::withMessages([
                    'registration' => 'This attendee has already checked in and cannot be cancelled.',
                ]);
            }

            $locked->update([
                'status' => RegistrationStatus::Cancelled->value,
                'cancelled_by_user_id' => $actor->id,
                'cancelled_at' => now(),
            ]);

            $this->recordActivity->handle(
                organization: $event->organization,
                action: 'registration.status_changed',
                subjectType: 'registration',
                subjectId: $locked->id,
                actor: $actor,
                event: $event,
                summary: "Registration '{$locked->registration_code}' changed from confirmed to cancelled.",
                properties: [
                    'registration_id' => $locked->id,
                    'previous_status' => RegistrationStatus::Confirmed->value,
                    'new_status' => RegistrationStatus::Cancelled->value,
                    'reason' => $reason,
                ],
            );

            return $locked->refresh();
        });
    }

    private function isCheckedIn(Registration $registration): bool
    {
        if (! Schema::hasTable('check_ins')) {
            return false;
        }

        return DB::table('check_ins')
            ->where('registration_id', $registration->id)
            ->exists();
    }
}
