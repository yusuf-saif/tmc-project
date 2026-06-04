<?php

use App\Livewire\Onboarding\OnboardingWizard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'))->name('landing');
Route::get('/offline', fn () => view('offline'))->name('offline');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', OnboardingWizard::class)->name('onboarding');
});

Route::get('/home', function () {
    return view('home-placeholder');
})->middleware(['auth'])->name('home');

Route::get('/logout', function () {
    Auth::guard('web')->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->middleware(['auth']);
