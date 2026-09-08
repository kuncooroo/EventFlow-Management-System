<?php

namespace App\Livewire\Events;

use App\Actions\Events\CreateRegistrationField;
use App\Actions\Events\ReorderRegistrationFields;
use App\Actions\Events\UpdateRegistrationField;
use App\Enums\RegistrationFieldType;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class RegistrationFieldManager extends Component
{
    public Event $event;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $label = '';

    public string $field_type = '';

    public string $options_text = '';

    public bool $is_required = false;

    public function mount(Event $event): void
    {
        $this->event = $event;

        Gate::authorize('update', $this->event);
    }

    public function beginCreate(): void
    {
        $this->resetErrorBag();
        $this->resetFields();
        $this->field_type = RegistrationFieldType::Text->value;
        $this->showForm = true;
    }

    public function beginEdit(int $id): void
    {
        $field = $this->event->registrationFields()->findOrFail($id);

        $this->resetErrorBag();
        $this->editingId = $field->id;
        $this->label = $field->label;
        $this->field_type = $field->field_type?->value ?? '';
        $this->options_text = implode("\n", $field->options());
        $this->is_required = $field->is_required;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetErrorBag();
        $this->resetFields();
        $this->showForm = false;
    }

    public function save(CreateRegistrationField $create, UpdateRegistrationField $update): void
    {
        Gate::authorize('update', $this->event);

        $this->validateForm();

        try {
            $data = [
                'label' => $this->label,
                'field_type' => $this->field_type,
                'options' => $this->parseOptions(),
                'is_required' => $this->is_required,
            ];

            if ($this->editingId) {
                $field = $this->event->registrationFields()->findOrFail($this->editingId);
                $update->handle($field, auth()->user(), $data);
                session()->flash('status', 'Custom field updated.');
            } else {
                $create->handle($this->event, auth()->user(), $data);
                session()->flash('status', 'Custom field added.');
            }

            $this->resetFields();
            $this->showForm = false;
        } catch (ValidationException $e) {
            foreach ($e->errors() as $fieldName => $messages) {
                $this->addError($fieldName === 'options' ? 'options_text' : $fieldName, $messages[0]);
            }
        }
    }

    public function delete(int $id): void
    {
        Gate::authorize('update', $this->event);

        $this->event->registrationFields()->findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetFields();
            $this->showForm = false;
        }

        session()->flash('status', 'Custom field deleted.');
    }

    public function setActive(int $id, bool $active): void
    {
        Gate::authorize('update', $this->event);

        $this->event->registrationFields()->findOrFail($id)->update(['is_active' => $active]);

        session()->flash('status', $active
            ? 'Custom field activated.'
            : 'Custom field deactivated. Historical responses are preserved.');
    }

    public function moveUp(int $id, ReorderRegistrationFields $action): void
    {
        $this->move($id, $action, -1);
    }

    public function moveDown(int $id, ReorderRegistrationFields $action): void
    {
        $this->move($id, $action, 1);
    }

    private function move(int $id, ReorderRegistrationFields $action, int $direction): void
    {
        Gate::authorize('update', $this->event);

        $orderedIds = $this->event->registrationFields()->ordered()->pluck('id')->map(fn ($value) => (int) $value)->all();

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
            session()->flash('status', 'Custom field order updated.');
        } catch (ValidationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function validateForm(): void
    {
        $this->validate([
            'label' => 'required|string|max:200',
            'field_type' => ['required', Rule::in(array_column(RegistrationFieldType::cases(), 'value'))],
            'is_required' => 'boolean',
        ]);

        $type = RegistrationFieldType::tryFrom($this->field_type);
        $options = $this->parseOptions();

        if ($type?->requiresOptions() && $options === []) {
            $this->addError('options_text', 'Options are required for '.strtolower($type->label()).' fields.');

            return;
        }

        if ($type?->requiresOptions() && count($options) > 100) {
            $this->addError('options_text', 'A field cannot have more than 100 options.');
        }
    }

    private function parseOptions(): array
    {
        return array_values(array_filter(
            preg_split('/\r\n|\r|\n/', $this->options_text) ?: [],
            fn ($option) => trim((string) $option) !== ''
        ));
    }

    private function resetFields(): void
    {
        $this->editingId = null;
        $this->label = '';
        $this->field_type = '';
        $this->options_text = '';
        $this->is_required = false;
    }

    public function render()
    {
        return view('livewire.events.registration-field-manager', [
            'fields' => $this->event->registrationFields()->ordered()->get(),
            'types' => RegistrationFieldType::cases(),
        ]);
    }
}
