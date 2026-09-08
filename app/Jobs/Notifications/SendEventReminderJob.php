<?php

namespace App\Jobs\Notifications;

use App\Models\Registration;
use App\Notifications\Events\EventReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendEventReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Registration $registration,
    ) {}

    public function handle(): void
    {
        Notification::route('mail', $this->registration->attendee_email)
            ->notifyNow(new EventReminderNotification($this->registration));
    }

    public function failed(Throwable $e): void
    {
        report($e);
    }
}
