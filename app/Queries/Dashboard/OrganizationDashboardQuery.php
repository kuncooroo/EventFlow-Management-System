<?php

namespace App\Queries\Dashboard;

use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrganizationDashboardQuery
{
    /**
     * Org-scoped dashboard aggregates, limited to the events the user may access.
     *
     * @return array{
     *     total_events: int,
     *     active_events: int,
     *     upcoming_events: int,
     *     ongoing_events: int,
     *     completed_events: int,
     *     status_counts: array<string, int>,
     *     total_registrations: int,
     *     confirmed_registrations: int,
     *     checked_in: int,
     *     upcoming: Collection<int, Event>,
     *     recent: Collection<int, Event>,
     * }
     */
    public function __invoke(Organization $organization, User $user): array
    {
        $eventScope = $this->accessibleEventScope($organization, $user);

        $statusCounts = (clone $eventScope)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [];

        foreach (EventStatus::cases() as $status) {
            $counts[$status->value] = (int) ($statusCounts[$status->value] ?? 0);
        }

        $registrations = Registration::query()
            ->whereIn('event_id', (clone $eventScope)->select('id'));

        $checkedIn = CheckIn::query()->whereIn(
            'registration_id',
            Registration::query()
                ->whereIn('event_id', (clone $eventScope)->select('id'))
                ->select('id'),
        );

        return [
            'total_events' => array_sum($counts),
            'active_events' => $counts[EventStatus::Published->value] + $counts[EventStatus::Ongoing->value],
            'upcoming_events' => $counts[EventStatus::Published->value],
            'ongoing_events' => $counts[EventStatus::Ongoing->value],
            'completed_events' => $counts[EventStatus::Completed->value],
            'status_counts' => $counts,
            'total_registrations' => $registrations->count(),
            'confirmed_registrations' => (clone $registrations)
                ->where('status', RegistrationStatus::Confirmed->value)
                ->count(),
            'checked_in' => $checkedIn->count(),
            'upcoming' => (clone $eventScope)
                ->where('status', EventStatus::Published->value)
                ->orderBy('start_at')
                ->limit(5)
                ->get(),
            'recent' => (clone $eventScope)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Base event query for the organization, restricted to the events the user
     * may access (DASH-001).
     */
    private function accessibleEventScope(Organization $organization, User $user): Builder
    {
        $query = Event::query()->where('organization_id', $organization->id);

        $membership = $organization->memberships()
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if ($membership === null) {
            return $query->whereRaw('1 = 0');
        }

        if (in_array($membership->role, [OrganizationRole::Owner, OrganizationRole::Admin], true)) {
            return $query;
        }

        return $query->whereHas(
            'assignments',
            fn (Builder $assignment) => $assignment->where('organization_membership_id', $membership->id),
        );
    }
}
