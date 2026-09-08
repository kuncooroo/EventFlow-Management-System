<?php

namespace App\Livewire\Events;

use App\Actions\Events\CreateTicketType;
use App\Actions\Events\ReorderTicketTypes;
use App\Actions\Events\UpdateTicketType;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TicketTypeManager extends Component
{
    public Event $event;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $description = null;

    public string $price_amount = '0';

    public string $currency = '';

    public ?string $capacity = null;

    public ?string $available_from = null;

    public ?string $available_until = null;

    public function mount(Event $event): void
    {
        $this->event = $event;

        $this->currency = $event->organization->default_currency ?? '';

        Gate::authorize('update', $this->event);
    }

    public function beginCreate(): void
    {
        $this->resetErrorBag();
        $this->resetFields();
        $this->showForm = true;
    }

    public function beginEdit(int $id): void
    {
        $ticketType = $this->event->ticketTypes()->findOrFail($id);

        $this->resetErrorBag();
        $this->editingId = $ticketType->id;
        $this->name = $ticketType->name;
        $this->description = $ticketType->description;
        $this->price_amount = number_format((float) $ticketType->price_amount, 2, '.', '');
        $this->currency = $ticketType->currency ?? '';
        $this->capacity = $ticketType->capacity === null ? null : (string) $ticketType->capacity;
        $this->available_from = $ticketType->available_from?->format('Y-m-d\TH:i');
        $this->available_until = $ticketType->available_until?->format('Y-m-d\TH:i');
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetErrorBag();
        $this->resetFields();
        $this->showForm = false;
    }

    public function save(CreateTicketType $create, UpdateTicketType $update): void
    {
        Gate::authorize('update', $this->event);

        $this->validateForm();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'price_amount' => $this->price_amount,
            'currency' => $this->currency,
            'capacity' => $this->capacity,
            'available_from' => $this->available_from,
            'available_until' => $this->available_until,
        ];

        try {
            if ($this->editingId) {
                $ticketType = $this->event->ticketTypes()->findOrFail($this->editingId);
                $update->handle($ticketType, auth()->user(), $data);
                session()->flash('status', 'Ticket type updated.');
            } else {
                $create->handle($this->event, auth()->user(), $data);
                session()->flash('status', 'Ticket type added.');
            }

            $this->resetFields();
            $this->showForm = false;
        } catch (ValidationException $e) {
            foreach ($e->errors() as $fieldName => $messages) {
                $this->addError($fieldName, $messages[0]);
            }
        }
    }

    public function delete(int $id): void
    {
        Gate::authorize('update', $this->event);

        $this->event->ticketTypes()->findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetFields();
            $this->showForm = false;
        }

        session()->flash('status', 'Ticket type deleted.');
    }

    public function setActive(int $id, bool $active): void
    {
        Gate::authorize('update', $this->event);

        $this->event->ticketTypes()->findOrFail($id)->update(['is_active' => $active]);

        session()->flash('status', $active
            ? 'Ticket type activated.'
            : 'Ticket type deactivated. It will not be selectable on the registration form.');
    }

    public function moveUp(int $id, ReorderTicketTypes $action): void
    {
        $this->move($id, $action, -1);
    }

    public function moveDown(int $id, ReorderTicketTypes $action): void
    {
        $this->move($id, $action, 1);
    }

    private function move(int $id, ReorderTicketTypes $action, int $direction): void
    {
        Gate::authorize('update', $this->event);

        $orderedIds = $this->event->ticketTypes()->ordered()->pluck('id')->map(fn ($value) => (int) $value)->all();

        $index = array_search($id, $orderedIds, true);

        if ($index === false) {
            return;
        }

        $target = $index + $direction;

        if ($target < 0 || $target >= count($orderedIds)) {
            return;
        }

        [$orderedIds[$index], $orderedIds[$target]] = [$orderedIds[$target], $orderedIds[$index]];

        try {
            $action->handle($this->event, auth()->user(), $orderedIds);
            session()->flash('status', 'Ticket type order updated.');
        } catch (ValidationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function validateForm(): void
    {
        $this->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price_amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'capacity' => 'nullable|integer|min:0',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after_or_equal:available_from',
        ]);

        if ((float) $this->price_amount > 0 && trim($this->currency) === '') {
            $this->addError('currency', 'A currency code is required when a price is set.');
        }
    }

    private function resetFields(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = null;
        $this->price_amount = '0';
        $this->capacity = null;
        $this->available_from = null;
        $this->available_until = null;
    }

    public function render()
    {
        return view('livewire.events.ticket-type-manager', [
            'ticketTypes' => $this->event->ticketTypes()->ordered()->get(),
        ]);
    }
}
