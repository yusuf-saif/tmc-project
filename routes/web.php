<?php

use App\Livewire\Events\EventDetail;
use App\Livewire\Events\EventsList;
use App\Livewire\Home\HomeDashboard;
use App\Livewire\Journal\JournalScreen;
use App\Livewire\Onboarding\OnboardingWizard;
use App\Livewire\Resources\ResourceDetail;
use App\Livewire\Resources\ResourcesLibrary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'))->name('landing');
Route::get('/offline', fn () => view('offline'))->name('offline');

Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding', OnboardingWizard::class)->name('onboarding');
});

Route::middleware(['auth', 'onboarded'])->group(function () {
    Route::get('/home', HomeDashboard::class)->name('home');
    Route::get('/events', EventsList::class)->name('events');
    Route::get('/events/{slug}', EventDetail::class)->name('events.show');
    Route::get('/resources', ResourcesLibrary::class)->name('resources');
    Route::get('/resources/{slug}', ResourceDetail::class)->name('resources.show');
    Route::get('/journal', JournalScreen::class)->name('journal');
});

Route::get('/logout', function () {
    Auth::guard('web')->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->middleware(['auth']);
