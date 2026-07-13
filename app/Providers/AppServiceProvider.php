<?php

namespace App\Providers;

use App\Events\BusinessActivated;
use App\Events\BusinessApproved;
use App\Events\BusinessSuspended;
use App\Events\MemberOnboardingCompleted;
use App\Events\MembershipActivated;
use App\Events\MembershipApproved;
use App\Events\MembershipNeedsCorrection;
use App\Events\MembershipRejected;
use App\Events\MembershipSubmitted;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionExpiringSoon;
use App\Events\SubscriptionPaymentFailed;
use App\Events\SubscriptionPaymentReceived;
use App\Events\SubscriptionSuspended;
use App\Listeners\AwardReferralCoins;
use App\Listeners\AwardWelcomeCoins;
use App\Listeners\LogBillingEvent;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogMembershipEvent;
use App\Listeners\LogOnboardingEvent;
use App\Listeners\SendBillingNotifications;
use App\Listeners\SendMembershipNotifications;
use App\Listeners\SendWelcomePush;
use App\Services\HijriDateService;
use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        if (app()->environment('local')) {
            Model::preventLazyLoading();
        }

        Paginator::useTailwind();

        $this->normalizeExistingEmails();

        Carbon::macro('hijri', function (string $format = 'd M Y'): string {
            return app(HijriDateService::class)->formatHijriDate($this, $format);
        });

        $events = [
            MembershipSubmitted::class,
            MembershipApproved::class,
            MembershipRejected::class,
            MembershipNeedsCorrection::class,
        ];

        foreach ($events as $event) {
            Event::listen($event, LogMembershipEvent::class);
            Event::listen($event, SendMembershipNotifications::class);
        }

        $subscriptionEvents = [
            SubscriptionActivated::class,
            SubscriptionExpiringSoon::class,
            SubscriptionPaymentReceived::class,
            SubscriptionPaymentFailed::class,
            SubscriptionSuspended::class,
            SubscriptionExpired::class,
        ];

        foreach ($subscriptionEvents as $event) {
            Event::listen($event, LogBillingEvent::class);
            Event::listen($event, SendBillingNotifications::class);
        }

        $businessEvents = [
            BusinessApproved::class,
            BusinessActivated::class,
            BusinessSuspended::class,
        ];

        foreach ($businessEvents as $event) {
            Event::listen($event, LogBillingEvent::class);
            Event::listen($event, SendBillingNotifications::class);
        }

        Event::listen(MembershipActivated::class, AwardReferralCoins::class);
        Event::listen(MembershipActivated::class, AwardWelcomeCoins::class);
        Event::listen(MembershipActivated::class, LogBillingEvent::class);
        Event::listen(MembershipActivated::class, SendMembershipNotifications::class);

        Event::listen(MemberOnboardingCompleted::class, AwardWelcomeCoins::class);
        Event::listen(MemberOnboardingCompleted::class, SendWelcomePush::class);
        Event::listen(MemberOnboardingCompleted::class, LogOnboardingEvent::class);

        Event::listen(Failed::class, LogFailedLogin::class);

        $this->validateDatabaseConnection();
    }

    protected function validateDatabaseConnection(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        if (app()->runningInConsole()) {
            try {
                DB::connection()->getPdo();
            } catch (\Throwable $e) {
                echo "\n⚠️  Database connection error: {$e->getMessage()}\n";
            }

            return;
        }

        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();

            $connection->select('SELECT 1');

            if ($driver === 'sqlite') {
                $path = $connection->getDatabaseName();

                if (! file_exists($path)) {
                    Log::warning('SQLite database file not found at: '.$path);

                    return;
                }

                Log::info('SQLite database connected', ['path' => $path]);
            }
        } catch (\Throwable $e) {
            Log::error('Database connection validation failed', [
                'message' => $e->getMessage(),
                'connection' => config('database.default'),
            ]);
        }
    }

    protected function normalizeExistingEmails(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $flag = storage_path('app/.emails_normalized');

        if (file_exists($flag) && file_get_contents($flag) === app()->version()) {
            return;
        }

        try {
            DB::table('users')->whereRaw('email != LOWER(email)')->update(['email' => DB::raw('LOWER(email)')]);
            DB::table('password_reset_tokens')->whereRaw('email != LOWER(email)')->update(['email' => DB::raw('LOWER(email)')]);

            if (DB::getSchemaBuilder()->hasTable('support_applications')) {
                DB::table('support_applications')->whereRaw('email != LOWER(email)')->update(['email' => DB::raw('LOWER(email)')]);
            }

            if (DB::getSchemaBuilder()->hasTable('souq_listings') && DB::getSchemaBuilder()->hasColumn('souq_listings', 'contact_email')) {
                DB::table('souq_listings')->whereRaw('contact_email != LOWER(contact_email)')->update(['contact_email' => DB::raw('LOWER(contact_email)')]);
            }

            file_put_contents($flag, app()->version());
        } catch (\Throwable $e) {
            Log::warning('Email normalization failed on boot', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
