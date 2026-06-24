<?php

namespace App\Providers;

use App\Events\BusinessActivated;
use App\Events\BusinessApproved;
use App\Events\BusinessSuspended;
use App\Events\MembershipActivated;
use App\Events\MembershipApproved;
use App\Events\MembershipNeedsCorrection;
use App\Events\MembershipRejected;
use App\Events\MembershipSubmitted;
use App\Events\PaymentConfirmed;
use App\Events\PaymentSubmitted;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionExpiringSoon;
use App\Events\SubscriptionPaymentFailed;
use App\Events\SubscriptionPaymentReceived;
use App\Events\SubscriptionSuspended;
use App\Listeners\AwardReferralCoins;
use App\Listeners\LogBillingEvent;
use App\Listeners\LogMembershipEvent;
use App\Listeners\SendBillingNotifications;
use App\Listeners\SendMembershipNotifications;
use App\Services\HijriDateService;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Model;
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

        Carbon::macro('hijri', function (string $format = 'd M Y'): string {
            return app(HijriDateService::class)->formatHijriDate($this, $format);
        });

        Event::listen(Verified::class, AwardReferralCoins::class);

        $events = [
            MembershipSubmitted::class,
            MembershipApproved::class,
            MembershipRejected::class,
            MembershipNeedsCorrection::class,
            PaymentSubmitted::class,
            PaymentConfirmed::class,
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

        Event::listen(MembershipActivated::class, LogBillingEvent::class);
        Event::listen(MembershipActivated::class, SendMembershipNotifications::class);

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

                try {
                    $pdo = $connection->getPdo();
                    $pdo->exec('PRAGMA journal_mode=WAL');
                    $pdo->exec('PRAGMA busy_timeout=5000');
                    $pdo->exec('PRAGMA synchronous=NORMAL');
                } catch (\Throwable $e) {
                    Log::warning('Could not set SQLite PRAGMAs: '.$e->getMessage());
                }

                Log::info('SQLite database connected with WAL mode', ['path' => $path]);
            }
        } catch (\Throwable $e) {
            Log::error('Database connection validation failed', [
                'message' => $e->getMessage(),
                'connection' => config('database.default'),
            ]);
        }
    }
}
