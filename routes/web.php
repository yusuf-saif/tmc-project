<?php

use App\Livewire\Events\EventDetail;
use App\Livewire\Events\EventsList;
use App\Livewire\Home\HomeDashboard;
use App\Livewire\Journal\JournalScreen;
use App\Livewire\Onboarding\OnboardingWizard;
use App\Livewire\Profile\EditProfile;
use App\Livewire\Profile\LegacyCard;
use App\Livewire\Profile\NotificationPreferences;
use App\Livewire\Profile\ProfileScreen;
use App\Livewire\Community\CommunityHome;
use App\Livewire\Community\SpaceDetail;
use App\Livewire\Community\SupportForm;
use App\Livewire\Resources\ResourceDetail;
use App\Livewire\Resources\ResourcesLibrary;
use App\Livewire\Souq\ApplyForm;
use App\Livewire\Souq\ListingDetail;
use App\Livewire\Souq\SouqDirectory;
use App\Livewire\Wallet\WalletScreen;
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
    Route::get('/community', CommunityHome::class)->name('community');
    Route::get('/community/spaces/{slug}', SpaceDetail::class)->name('community.spaces.show');
    Route::get('/community/support/{type}', SupportForm::class)->name('community.support');
    Route::get('/community/donate', function () {
        $bankDetails = \App\Models\Setting::getValue('bank_details', 'Contact us for bank details');
        $donateMessage = \App\Models\Setting::getValue('donate_message', 'JazakAllahu Khairan for your generous support.');

        return view('community.donate', compact('bankDetails', 'donateMessage'));
    })->name('community.donate');
    Route::get('/souq', SouqDirectory::class)->name('souq');
    Route::get('/souq/apply', ApplyForm::class)->name('souq.apply');
    Route::get('/souq/{slug}', ListingDetail::class)->name('souq.show');
    Route::get('/wallet', WalletScreen::class)->name('wallet');
    Route::get('/profile', ProfileScreen::class)->name('profile');
    Route::get('/profile/edit', EditProfile::class)->name('profile.edit');
    Route::get('/profile/legacy-card', LegacyCard::class)->name('profile.legacy-card');
    Route::get('/profile/notifications', NotificationPreferences::class)->name('profile.notifications');
    Route::get('/announcements/{slug}', function ($slug) {
        $announcement = \App\Models\Announcement::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('announcements.show', compact('announcement'));
    })->name('announcements.show');
});

Route::post('/logout', function () {
    Auth::guard('web')->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->middleware(['auth'])->name('logout');

Route::get('/logout', function () {
    Auth::guard('web')->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->middleware(['auth']);
