<?php

namespace App\Actions\ActivityLogs;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;

class RecordActivity
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function handle(
        Organization $organization,
        string $action,
        string $subjectType,
        int $subjectId,
        ?User $actor = null,
        ?Event $event = null,
        ?string $summary = null,
        array $properties = []
    ): ActivityLog {
        return ActivityLog::create([
            'organization_id' => $organization->id,
            'event_id' => $event?->id,
            'actor_user_id' => $actor?->id,
            'actor_label' => $actor?->name,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'summary' => $summary,
            'properties' => $properties,
        ]);
    }
}
