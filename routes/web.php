<?php

use App\Http\Controllers\Organizations\OrganizationController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Public\EventPageController;
use App\Http\Controllers\Public\TicketAccessController;
use App\Http\Controllers\Reports\ExportController;
use App\Livewire\Attendees\AttendeeIndex;
use App\Livewire\Attendees\AttendeeShow;
use App\Livewire\CheckIn\ManualCheckInSearch;
use App\Livewire\Events\EventForm;
use App\Livewire\Events\EventIndex;
use App\Livewire\Events\EventSetup;
use App\Livewire\Organizations\AcceptInvitation;
use App\Livewire\Organizations\MemberIndex;
use App\Livewire\Organizations\SettingsForm;
use App\Livewire\Public\RegistrationForm;
use App\Livewire\Reports\AttendanceReport;
use App\Livewire\Reports\EventDashboard;
use App\Livewire\Reports\OrganizationDashboard;
use App\Livewire\Reports\RegistrationReport;
use App\Livewire\Reports\TicketTypeReport;
use App\Livewire\Smoke\FoundationSmoke;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('pages.home'))->name('home');

Route::get('/foundation', function () {
    abort_if(app()->isProduction(), 404);

    return view('pages.public.foundation');
})->name('public.foundation');

Route::get('/e/{slug}', [EventPageController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('public.events.show');

Route::get('/e/{slug}/register', RegistrationForm::class)
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('public.events.register');

Route::get('/t/{token}', [TicketAccessController::class, 'show'])
    ->middleware('throttle:tickets')
    ->where('token', '[A-Za-z0-9]+')
    ->name('tickets.public.show');

/*
|--------------------------------------------------------------------------
| Authenticated organizer routes
|--------------------------------------------------------------------------
*/
Route::prefix('app')->name('app.')->group(function () {
    Route::get('/invitations/{token}', AcceptInvitation::class)->name('invitations.show');

    Route::middleware('auth')->group(function () {
        Route::get('/organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
        Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
        Route::post('/organizations/switch', [OrganizationController::class, 'switch'])->name('organizations.switch');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::middleware('organization')->group(function () {
            Route::get('/dashboard', OrganizationDashboard::class)->name('dashboard');
            Route::get('/members', MemberIndex::class)->name('members.index');
            Route::get('/settings', SettingsForm::class)->name('settings.index');

            // Events
            Route::get('/events', EventIndex::class)->name('events.index');
            Route::get('/events/create', EventForm::class)->name('events.create');
            Route::get('/events/{event}/edit', EventForm::class)->name('events.edit');
            Route::get('/events/{event}/setup', EventSetup::class)->name('events.setup');
            Route::get('/events/{event}/dashboard', EventDashboard::class)->name('events.dashboard');
            Route::get('/events/{event}/attendees', AttendeeIndex::class)->name('events.attendees.index');
            Route::get('/events/{event}/attendees/{registration}', AttendeeShow::class)->name('events.attendees.show');
            Route::get('/events/{event}/check-in', ManualCheckInSearch::class)->name('events.check-in.index');

            // Reports
            Route::get('/reports/registrations', RegistrationReport::class)->name('reports.registrations');
            Route::get('/reports/ticket-types', TicketTypeReport::class)->name('reports.ticket-types');
            Route::get('/reports/attendance', AttendanceReport::class)->name('reports.attendance');
            Route::get('/reports/export', [ExportController::class, 'download'])->name('reports.export');
        });
    });
});

Route::get('/livewire-smoke', FoundationSmoke::class)->name('livewire.smoke');
