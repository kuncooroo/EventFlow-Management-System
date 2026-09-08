<?php

namespace App\Livewire\Events;

use App\Actions\Events\CreateEvent;
use App\Actions\Events\UpdateEvent;
use App\Models\Event;
use App\Models\Organization;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class EventForm extends Component
{
    use AuthorizesRequests;

    public Organization $organization;

    public ?Event $event = null;

    public bool $isEditing = false;

    // Form fields
    public string $name = '';

    public string $description = '';

    public string $organizer_name = '';

    public string $mode = '';

    public string $start_at = '';

    public string $end_at = '';

    public string $capacity = '';

    public function mount(OrganizationContext $context, ?Event $event = null): void
    {
        $organization = $context->resolveForUser(auth()->user());

        if ($organization === null) {
            abort(403);
        }

        $this->organization = $organization;

        if ($event !== null) {
            // Ensure event belongs to this org
            abort_if($event->organization_id !== $organization->id, 404);
            $this->authorize('update', $event);

            $this->event = $event;
            $this->isEditing = true;

            $this->name = $event->name;
            $this->description = $event->description ?? '';
            $this->organizer_name = $event->organizer_name ?? '';
            $this->mode = $event->mode ?? '';
            $this->start_at = $event->start_at?->format('Y-m-d\TH:i') ?? '';
            $this->end_at = $event->end_at?->format('Y-m-d\TH:i') ?? '';
            $this->capacity = (string) ($event->capacity ?? '');
        } else {
            $this->authorize('create', Event::class);
        }
    }

    public function getTitle(): string
    {
        return $this->isEditing ? 'Edit Event' : 'Create Event';
    }

    public function save(CreateEvent $createAction, UpdateEvent $updateAction): void
    {
        $this->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'organizer_name' => 'nullable|string|max:150',
            'mode' => 'nullable|in:online,offline,hybrid',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'capacity' => 'nullable|integer|min:0',
        ]);

        $data = [
            'description' => $this->description ?: null,
            'organizer_name' => $this->organizer_name ?: null,
            'mode' => $this->mode ?: null,
            'start_at' => $this->start_at ?: null,
            'end_at' => $this->end_at ?: null,
            'capacity' => $this->capacity !== '' ? (int) $this->capacity : null,
        ];

        try {
            if ($this->isEditing) {
                $updateAction->handle($this->event, auth()->user(), array_merge(['name' => $this->name], $data));
                session()->flash('status', 'Event updated successfully.');
            } else {
                $event = $createAction->handle($this->organization, auth()->user(), $this->name, $data);
                session()->flash('status', 'Event created successfully.');
                $this->redirectRoute('app.events.setup', $event);

                return;
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        $this->redirectRoute('app.events.index');
    }

    public function render(): View
    {
        return view('livewire.events.event-form', [
            'title' => $this->getTitle(),
        ]);
    }
}
