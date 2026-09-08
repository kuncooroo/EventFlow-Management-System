<?php

namespace App\Actions\Events;

use App\Actions\ActivityLogs\RecordActivity;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Services\Events\PublishReadinessChecker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublishEvent
{
    public function __construct(
        private readonly PublishReadinessChecker $readiness,
        private readonly RecordActivity $recordActivity,
    ) {}

    public function handle(Event $event, User $actor): Event
    {
        Gate::forUser($actor)->authorize('publish', $event);

        $failures = $this->readiness->failures($event);
        $failures = $this->ensureSlug($event, $failures);

        if ($failures !== []) {
            throw ValidationException::withMessages([
                'publish' => $this->messageFor($failures),
            ]);
        }

        return DB::transaction(function () use ($event, $actor): Event {
            $event->update([
                'status' => EventStatus::Published->value,
                'published_at' => now(),
            ]);

            $this->recordActivity->handle(
                organization: $event->organization,
                action: 'event.published',
                subjectType: 'event',
                subjectId: $event->id,
                actor: $actor,
                event: $event,
                summary: "Event '{$event->name}' published.",
                properties: ['event_id' => $event->id, 'public_slug' => $event->public_slug],
            );

            return $event->refresh();
        });
    }

    /**
     * Ensure a unique public slug is populated. Returns failures augmented with
     * any slug-generation problem.
     *
     * @param  array<int, string>  $failures
     * @return array<int, string>
     */
    private function ensureSlug(Event $event, array $failures): array
    {
        $slug = $event->public_slug;

        if ($slug === null || trim($slug) === '') {
            $slug = $this->uniqueSlug($event->name, $event->id);
            $event->public_slug = $slug;
            $event->save();
            $failures = array_values(array_diff($failures, ['A public URL (slug) is required.']));

            return $failures;
        }

        return $failures;
    }

    private function uniqueSlug(string $name, int $ignoreId): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'event';
        $slug = $baseSlug;
        $counter = 2;

        while (Event::where('public_slug', $slug)->whereKeyNot($ignoreId)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<int, string>  $failures
     */
    private function messageFor(array $failures): string
    {
        $list = implode(' ', array_map(fn (string $f): string => '- '.$f, $failures));

        return 'This event is not ready to publish. '.$list;
    }
}
