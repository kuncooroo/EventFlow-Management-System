<?php

namespace App\Livewire\Events;

use App\Actions\Events\CreateAgendaItem;
use App\Actions\Events\ReorderAgendaItems;
use App\Actions\Events\UpdateAgendaItem;
use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AgendaManager extends Component
{
    public Event $event;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $start_at = '';

    public string $end_at = '';

    public string $location = '';

    public string $speaker_text = '';

    public string $description = '';

    public function mount(Event $event): void
    {
        $this->event = $event;

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
        $item = $this->event->agendaItems()->findOrFail($id);

        $this->resetErrorBag();
        $this->editingId = $item->id;
        $this->title = $item->title;
        $this->start_at = $item->start_at?->format('Y-m-d\TH:i') ?? '';
        $this->end_at = $item->end_at?->format('Y-m-d\TH:i') ?? '';
        $this->location = $item->location ?? '';
        $this->speaker_text = $item->speaker_text ?? '';
        $this->description = $item->description ?? '';
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetErrorBag();
        $this->resetFields();
        $this->showForm = false;
    }

    public function save(CreateAgendaItem $create, UpdateAgendaItem $update): void
    {
        Gate::authorize('update', $this->event);

        $this->validateForm();

        try {
            if ($this->editingId) {
                $item = $this->event->agendaItems()->findOrFail($this->editingId);
                $update->handle($item, auth()->user(), $this->formData());
                session()->flash('status', 'Agenda item updated.');
            } else {
                $create->handle($this->event, auth()->user(), $this->formData());
                session()->flash('status', 'Agenda item added.');
            }

            $this->resetFields();
            $this->showForm = false;
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    public function delete(int $id): void
    {
        Gate::authorize('update', $this->event);

        if ($this->event->status !== EventStatus::Draft) {
            session()->flash('error', 'Only Draft events can have agenda items managed.');

            return;
        }

        $this->event->agendaItems()->findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetFields();
            $this->showForm = false;
        }

        session()->flash('status', 'Agenda item deleted.');
    }

    public function moveUp(int $id, ReorderAgendaItems $action): void
    {
        $this->move($id, $action, -1);
    }

    public function moveDown(int $id, ReorderAgendaItems $action): void
    {
        $this->move($id, $action, 1);
    }

    private function move(int $id, ReorderAgendaItems $action, int $direction): void
    {
        Gate::authorize('update', $this->event);

        $orderedIds = $this->event->agendaItems()->ordered()->pluck('id')->map(fn ($value) => (int) $value)->all();

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
            session()->flash('status', 'Agenda order updated.');
        } catch (ValidationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function validateForm(): void
    {
        $this->validate([
            'title' => 'required|string|max:200',
            'start_at' => 'required',
            'end_at' => 'nullable',
            'location' => 'nullable|string|max:200',
            'speaker_text' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
    }

    private function formData(): array
    {
        return [
            'title' => $this->title,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'location' => $this->location,
            'speaker_text' => $this->speaker_text,
            'description' => $this->description,
        ];
    }

    private function resetFields(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->start_at = '';
        $this->end_at = '';
        $this->location = '';
        $this->speaker_text = '';
        $this->description = '';
    }

    public function render()
    {
        return view('livewire.events.agenda-manager', [
            'items' => $this->event->agendaItems()->ordered()->get(),
        ]);
    }
}
