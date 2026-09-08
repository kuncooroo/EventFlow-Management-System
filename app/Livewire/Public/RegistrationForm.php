<?php

namespace App\Livewire\Public;

use App\Actions\Registrations\RegisterAttendee;
use App\Enums\RegistrationFieldType;
use App\Models\Event;
use App\Models\Registration;
use App\Queries\Public\PublicEventQuery;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.public')]
#[Title('Register')]
class RegistrationForm extends Component
{
    public Event $event;

    public string $eventSlug;

    public string $attendee_name = '';

    public string $attendee_email = '';

    public ?string $attendee_phone = null;

    public ?string $attendee_organization = null;

    public ?int $ticket_type_id = null;

    /** @var array<string, string|string[]> */
    public array $answers = [];

    public bool $submitted = false;

    public ?Registration $registration = null;

    public function mount(string $slug): void
    {
        $this->eventSlug = $slug;

        $query = new PublicEventQuery;
        $event = $query->resolve($slug);

        if ($event === null) {
            abort(404);
        }

        $this->event = $event;
    }

    public function render(PublicEventQuery $query)
    {
        $cta = $query->ctaState($this->event);

        return view('livewire.public.registration-form', [
            'cta' => $cta,
            'ticketTypes' => $query->availableTicketTypes($this->event),
            'fields' => $this->event->registrationFields()->active()->ordered()->get(),
            'timezone' => $this->event->organization?->timezone ?? 'UTC',
        ]);
    }

    public function submit(RegisterAttendee $action): void
    {
        if ($this->submitted) {
            return;
        }

        $limiterKey = 'public-registration:'.(request()->ip() ?? 'unknown');

        if (RateLimiter::tooManyAttempts($limiterKey, 20)) {
            $this->addError('capacity', 'Too many registration attempts. Please try again shortly.');

            return;
        }

        RateLimiter::hit($limiterKey, 60);

        $query = new PublicEventQuery;
        $cta = $query->ctaState($this->event);

        if ($cta['state'] !== 'open') {
            $this->addError('capacity', 'Registration is no longer available.');

            return;
        }

        $this->validate($this->validationRules());

        try {
            $this->registration = $action->handle($this->event, [
                'attendee_name' => $this->attendee_name,
                'attendee_email' => $this->attendee_email,
                'attendee_phone' => $this->attendee_phone,
                'attendee_organization' => $this->attendee_organization,
                'ticket_type_id' => $this->ticket_type_id,
                'answers' => $this->answers,
            ]);

            $this->submitted = true;
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function validationRules(): array
    {
        $event = $this->event;
        $hasTicketTypes = $event->ticketTypes()->active()->count() > 0;

        $rules = [
            'attendee_name' => ['required', 'string', 'max:150'],
            'attendee_email' => ['required', 'email', 'max:254'],
            'attendee_phone' => $event->require_phone ? ['required', 'string', 'max:50'] : ['nullable', 'string', 'max:50'],
            'attendee_organization' => $event->require_organization ? ['required', 'string', 'max:180'] : ['nullable', 'string', 'max:180'],
            'ticket_type_id' => $hasTicketTypes ? ['required', 'integer'] : ['nullable'],
        ];

        $fields = $event->registrationFields()->active()->ordered()->get();

        foreach ($fields as $field) {
            $base = $field->is_required ? ['required'] : ['nullable'];

            $rules['answers.'.$field->id] = match ($field->field_type) {
                RegistrationFieldType::Text, RegistrationFieldType::Textarea, RegistrationFieldType::Date => array_merge($base, ['string']),
                RegistrationFieldType::Select, RegistrationFieldType::Radio => array_merge($base, ['string', 'in:'.implode(',', $field->options())]),
                RegistrationFieldType::Checkbox => array_merge($base, ['array']),
            };
        }

        return $rules;
    }
}
