<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Jobs\Notifications\SendEventReminderJob;
use App\Models\Event;
use App\Models\EventReminderSend;
use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendEventRemindersCommand extends Command
{
    protected $signature = 'send:event-reminders';

    protected $description = 'Send due event reminder notifications to eligible confirmed registrations';

    public function handle(): int
    {
        $sent = 0;

        foreach ($this->dueEvents() as $event) {
            $sent += $this->sendDue($event);
        }

        if ($sent > 0) {
            $this->info("Sent {$sent} event reminder notification(s).");
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Event>
     */
    private function dueEvents(): Collection
    {
        $now = Carbon::now();

        return Event::query()
            ->where('reminder_enabled', true)
            ->whereNotNull('start_at')
            ->whereNotNull('reminder_hours_before')
            ->whereNotIn('status', [
                EventStatus::Draft->value,
                EventStatus::Completed->value,
                EventStatus::Cancelled->value,
                EventStatus::Archived->value,
            ])
            ->get()
            ->filter(fn (Event $event) => $event->isReminderEligible($now));
    }

    private function sendDue(Event $event): int
    {
        $occurrenceKey = $this->occurrenceKey($event);

        $registrationIds = DB::transaction(function () use ($event, $occurrenceKey) {
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->first();

            if ($locked === null || ! $locked->isReminderEligible()) {
                return [];
            }

            if (EventReminderSend::where('event_id', $locked->id)
                ->where('occurrence_key', $occurrenceKey)
                ->exists()) {
                return [];
            }

            EventReminderSend::create([
                'event_id' => $locked->id,
                'occurrence_key' => $occurrenceKey,
                'sent_at' => Carbon::now(),
            ]);

            return $locked->registrations()
                ->where('status', RegistrationStatus::Confirmed->value)
                ->whereNull('cancelled_at')
                ->pluck('registrations.id')
                ->all();
        });

        foreach ($registrationIds as $registrationId) {
            SendEventReminderJob::dispatch(Registration::find($registrationId));
        }

        return count($registrationIds);
    }

    private function occurrenceKey(Event $event): string
    {
        return 'reminder:'.$event->start_at->getTimestamp();
    }
}
