<?php

namespace App\Queries\Reports\Concerns;

use App\Models\Organization;
use App\Models\User;
use App\Queries\Reports\AccessibleEventsQuery;
use Illuminate\Database\Eloquent\Builder;

trait ScopesToAccessibleEvents
{
    protected function accessibleEvents(Organization $organization, User $user): Builder
    {
        return app(AccessibleEventsQuery::class)($organization, $user);
    }

    /**
     * @return array<int, int>
     */
    protected function accessibleEventIds(Organization $organization, User $user): array
    {
        return $this->accessibleEvents($organization, $user)->pluck('id')->all();
    }
}
