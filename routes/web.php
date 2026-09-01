<?php

use App\Http\Controllers\Organizations\OrganizationController;
use App\Http\Controllers\Profile\ProfileController;
use App\Livewire\Organizations\MemberIndex;
use App\Livewire\Smoke\FoundationSmoke;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('pages.home'))->name('home');

Route::get('/foundation', fn () => view('pages.public.foundation'))->name('public.foundation');

/*
|--------------------------------------------------------------------------
| Authenticated organizer routes
|--------------------------------------------------------------------------
*/
Route::prefix('app')->name('app.')->middleware('auth')->group(function () {
    Route::get('/organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::post('/organizations/switch', [OrganizationController::class, 'switch'])->name('organizations.switch');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('organization')->group(function () {
        Route::get('/dashboard', fn () => view('pages.app.dashboard'))->name('dashboard');
        Route::get('/members', MemberIndex::class)->name('members.index');
    });
});

Route::get('/livewire-smoke', FoundationSmoke::class)->name('livewire.smoke');
