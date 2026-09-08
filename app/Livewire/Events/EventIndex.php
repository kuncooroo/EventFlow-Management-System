<?php

namespace App\Livewire\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Events')]
class EventIndex extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public Organization $organization;

    public string $search = '';

    public string $statusFilter = '';

    public function mount(OrganizationContext $context): void
    {
        $organization = $context->resolveForUser(auth()->user());

        if ($organization === null) {
            abort(403);
        }

        $this->organization = $organization;
        $this->authorize('viewAny', Event::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $user = auth()->user();
        $membership = $this->organization->memberships()
            ->where('user_id', $user->id)
            ->active()
            ->first();

        $isGlobal = $membership && in_array(
            $membership->role,
            [OrganizationRole::Owner, OrganizationRole::Admin]
        );

        $query = Event::query()
            ->where('organization_id', $this->organization->id)
            ->with(['createdBy'])
            ->when(! $isGlobal && $membership, function ($q) use ($membership) {
                // Restricted roles: only see assigned events
                $q->whereHas('assignments', fn ($a) => $a->where('organization_membership_id', $membership->id)
                );
            })
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')
            )
            ->when($this->statusFilter === '', function ($q) {
                // EBR-005: archived events are excluded from default active views.
                $q->where('status', '!=', EventStatus::Archived->value);
            })
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter)
            )
            ->orderByDesc('created_at');

        $events = $query->paginate(15);

        return view('livewire.events.event-index', [
            'events' => $events,
            'statuses' => EventStatus::cases(),
            'canCreate' => $user->can('create', Event::class),
        ]);
    }
}
