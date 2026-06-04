<?php

use App\Livewire\Home\HomeDashboard;
use App\Livewire\Onboarding\OnboardingWizard;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'))->name('landing');
Route::get('/offline', fn () => view('offline'))->name('offline');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', OnboardingWizard::class)->name('onboarding');
});

Route::middleware(['auth', 'verified', 'onboarded'])->group(function () {
    Route::get('/home', HomeDashboard::class)->name('home');
});
