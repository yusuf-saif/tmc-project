<?php

use App\Http\Controllers\Admin\ReceiptDownloadController;
use App\Http\Controllers\PaymentStatusController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\PushSubscriptionController;
use App\Livewire\Community\CommunityHome;
use App\Livewire\Community\SpaceDetail;
use App\Livewire\Community\SupportForm;
use App\Livewire\Events\EventDetail;
use App\Livewire\Events\EventsList;
use App\Livewire\Home\HomeDashboard;
use App\Livewire\Journal\JournalScreen;
use App\Livewire\Membership\MembershipSignupWizard;
use App\Livewire\Membership\PaymentPage;
use App\Livewire\Membership\PendingReview;
use App\Livewire\Profile\EditProfile;
use App\Livewire\Profile\LegacyCard;
use App\Livewire\Profile\NotificationPreferences;
use App\Livewire\Profile\ProfileScreen;
use App\Livewire\Resources\ResourceDetail;
use App\Livewire\Resources\ResourcesLibrary;
use App\Livewire\Souq\ApplyForm;
use App\Livewire\Souq\ListingDetail;
use App\Livewire\Souq\SouqDirectory;
use App\Models\Setting;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'))->name('landing');
Route::get('/offline', fn () => view('offline'))->name('offline');

// Membership signup — no auth middleware (wizard creates user + logs in internally)
Route::get('/membership/signup', MembershipSignupWizard::class)->name('membership.signup');

// Paystack webhook — CSRF-exempt, signature-enforced
Route::post('/webhooks/paystack', PaystackWebhookController::class)
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->name('webhooks.paystack');

Route::middleware(['auth'])->prefix('membership')->name('membership.')->group(function () {
    Route::get('/pending', PendingReview::class)->name('pending');
    Route::get('/payment', PaymentPage::class)->name('payment');
    Route::get('/payment/status', [PaymentStatusController::class, 'check'])->name('payment.status');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('/wallet', '/profile?tab=wallet')->name('wallet');

    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/subscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});

Route::middleware(['auth', 'ensure.user.state'])->group(function () {
    Route::get('/home', HomeDashboard::class)->name('home');
    Route::get('/events', EventsList::class)->name('events');
    Route::get('/events/{slug}', EventDetail::class)->name('events.show');
    Route::get('/resources', ResourcesLibrary::class)->name('resources');
    Route::get('/resources/{slug}', ResourceDetail::class)->name('resources.show');
    Route::get('/community', CommunityHome::class)->name('community');
    Route::get('/community/spaces/{slug}', SpaceDetail::class)->name('community.spaces.show');
    Route::get('/community/support/{type}', SupportForm::class)->name('community.support');
    Route::get('/community/donate', function () {
        $bankDetails = Setting::getValue('bank_details', 'Contact us for bank details');
        $donateMessage = Setting::getValue('donate_message', 'JazakAllahu Khairan for your generous support.');

        return view('community.donate', compact('bankDetails', 'donateMessage'));
    })->name('community.donate');
    Route::get('/profile', ProfileScreen::class)->name('profile');
    Route::get('/profile/edit', EditProfile::class)->name('profile.edit');
    Route::get('/profile/legacy-card', LegacyCard::class)->name('profile.legacy-card');
    Route::get('/profile/notifications', NotificationPreferences::class)->name('profile.notifications');

    // Restricted areas — require active membership
    Route::middleware(['not-suspended'])->group(function () {
        Route::get('/journal', JournalScreen::class)->name('journal');
        Route::get('/souq', SouqDirectory::class)->name('souq');
        Route::get('/souq/apply', ApplyForm::class)->name('souq.apply');
        Route::get('/souq/{slug}', ListingDetail::class)->name('souq.show');
    });
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/receipt/{memberProfile}', ReceiptDownloadController::class)->name('receipt.download');
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

Route::get('/password/change', fn () => view('auth.change-password'))
    ->middleware(['auth'])
    ->name('password.change');
