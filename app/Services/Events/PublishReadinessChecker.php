<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Models\Event;

class PublishReadinessChecker
{
    /**
     * @return array<int, string> human-readable readiness failures; empty when ready
     */
    public function failures(Event $event): array
    {
        $failures = [];

        if ($event->status !== EventStatus::Draft) {
            $failures[] = 'Only Draft events can be published.';
        }

        if ($event->name === null || trim($event->name) === '') {
            $failures[] = 'An event name is required.';
        }

        if ($event->start_at === null) {
            $failures[] = 'A start date/time is required.';
        }

        if ($event->end_at === null) {
            $failures[] = 'An end date/time is required.';
        }

        if ($event->organizer_name === null || trim((string) $event->organizer_name) === '') {
            $failures[] = 'An organizer is required.';
        }

        if ($event->public_slug === null || trim($event->public_slug) === '') {
            $failures[] = 'A public URL (slug) is required.';
        }

        return $failures;
    }

    public function isReady(Event $event): bool
    {
        return $this->failures($event) === [];
    }
}
