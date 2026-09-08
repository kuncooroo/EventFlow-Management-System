<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\ExportCsv;
use App\Enums\ExportType;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Policies\ReportPolicy;
use App\Queries\Reports\AccessibleEventsQuery;
use App\Support\Organization\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AccessibleEventsQuery $events,
    ) {}

    public function download(Request $request, OrganizationContext $context, ExportCsv $export): StreamedResponse
    {
        $organization = $context->resolveForUser($request->user());

        if ($organization === null) {
            abort(403);
        }

        $this->authorize('viewAny', [ReportPolicy::class]);

        $typeValue = (string) $request->input('type');

        $data = $request->validate([
            'type' => ['required', Rule::enum(ExportType::class)],
            'event_id' => ['nullable', 'integer', Rule::requiredIf($typeValue === ExportType::Attendees->value)],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(RegistrationStatus::class)],
            'ticket_type_id' => ['nullable', 'integer'],
            'checked_in' => ['nullable', Rule::in(['0', '1'])],
            'registered_from' => ['nullable', 'date'],
            'registered_to' => ['nullable', 'date'],
        ]);

        $type = ExportType::from($data['type']);

        $filters = array_filter([
            'event_id' => $data['event_id'] ?? null,
            'search' => $data['search'] ?? null,
            'status' => $data['status'] ?? null,
            'ticket_type_id' => $data['ticket_type_id'] ?? null,
            'checked_in' => $data['checked_in'] ?? null,
            'registered_from' => $data['registered_from'] ?? null,
            'registered_to' => $data['registered_to'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        if ($type === ExportType::Attendees) {
            $event = ($this->events)($organization, $request->user())->find($filters['event_id']);

            if ($event === null) {
                abort(404);
            }

            $this->authorize('view', [ReportPolicy::class, $event]);
        }

        return $export->stream($organization, $request->user(), $type, $filters);
    }
}
