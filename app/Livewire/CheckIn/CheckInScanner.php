<?php

namespace App\Livewire\CheckIn;

use App\Actions\CheckIns\CheckInAttendee;
use App\Actions\CheckIns\ResolveTicketForCheckIn;
use App\Data\CheckIns\CheckInResult;
use App\Enums\CheckInMethod;
use App\Enums\CheckInOutcome;
use App\Models\Event;
use App\Models\Ticket;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Check-In Scanner')]
class CheckInScanner extends Component
{
    use AuthorizesRequests;

    public Event $event;

    #[Rule(['required', 'string', 'max:64'])]
    public string $qrToken = '';

    public ?string $resultOutcome = null;

    public ?string $resultTitle = null;

    public ?string $resultDetail = null;

    private const SCAN_ATTEMPTS = 120;

    public function mount(OrganizationContext $context): void
    {
        $organization = $context->resolveForUser(auth()->user());

        if ($organization === null || $this->event->organization_id !== $organization->id) {
            abort(404);
        }

        $this->authorize('checkIn', $this->event);
    }

    public function updatingQrToken(): void
    {
        $this->clearResult();
    }

    #[On('scan-token')]
    public function scanToken(string $token): void
    {
        $this->qrToken = $token;
        $this->attempt();
    }

    public function attempt(): void
    {
        if (! $this->hasScanAttemptsLeft()) {
            $this->storeResult(CheckInResult::invalid('Too many scans. Wait a moment and try again.'), null);

            return;
        }

        $operator = auth()->user();

        $ticket = app(ResolveTicketForCheckIn::class)->handle($this->event, $this->qrToken);

        if ($ticket === null) {
            $this->storeResult(CheckInResult::invalid('This QR code cannot be used for this event.'), null);

            return;
        }

        $result = app(CheckInAttendee::class)->handle(
            $this->event,
            $ticket->registration,
            $operator,
            CheckInMethod::Qr,
            $ticket,
        );

        $this->storeResult($result, $ticket);

        $this->qrToken = '';
    }

    public function resetScanner(): void
    {
        $this->clearResult();
        $this->qrToken = '';
    }

    private function hasScanAttemptsLeft(): bool
    {
        $key = 'checkin-scan:'.$this->event->id.':'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, self::SCAN_ATTEMPTS)) {
            return false;
        }

        RateLimiter::hit($key, 60);

        return true;
    }

    private function clearResult(): void
    {
        $this->resultOutcome = null;
        $this->resultTitle = null;
        $this->resultDetail = null;
    }

    private function storeResult(CheckInResult $result, ?Ticket $ticket): void
    {
        $this->clearResult();

        $this->resultOutcome = $result->outcome->value;

        if ($result->outcome === CheckInOutcome::Success) {
            $ticket?->loadMissing('registration.ticketType');
            $this->resultTitle = $ticket?->registration->attendee_name;
            $this->resultDetail = 'Checked in at '.$result->checkIn->checked_in_at?->format('g:i A');
        } elseif ($result->outcome === CheckInOutcome::Duplicate) {
            $this->resultTitle = $ticket?->registration->attendee_name;
            $this->resultDetail = 'Previously checked in at '.$result->previousCheckIn->checked_in_at?->format('g:i A');
        } else {
            $this->resultDetail = $result->message ?? 'This entry cannot be used for this event.';
        }
    }

    public function render(): View
    {
        return view('livewire.check-in.check-in-scanner');
    }
}
