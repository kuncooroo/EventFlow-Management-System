@props(['active' => ''])

<nav class="flex gap-1 overflow-x-auto rounded-lg border border-slate-200 bg-white p-1 text-sm shadow-sm" aria-label="Reports">
    <a href="{{ route('app.reports.registrations') }}" @class([
        'whitespace-nowrap rounded-md px-3 py-1.5',
        'bg-teal-50 font-medium text-teal-700' => $active === 'registrations',
        'text-slate-600 hover:bg-slate-100' => $active !== 'registrations',
    ])>
        Registrations
    </a>
    <a href="{{ route('app.reports.ticket-types') }}" @class([
        'whitespace-nowrap rounded-md px-3 py-1.5',
        'bg-teal-50 font-medium text-teal-700' => $active === 'ticket-types',
        'text-slate-600 hover:bg-slate-100' => $active !== 'ticket-types',
    ])>
        Ticket Types
    </a>
    <a href="{{ route('app.reports.attendance') }}" @class([
        'whitespace-nowrap rounded-md px-3 py-1.5',
        'bg-teal-50 font-medium text-teal-700' => $active === 'attendance',
        'text-slate-600 hover:bg-slate-100' => $active !== 'attendance',
    ])>
        Attendance
    </a>
</nav>