<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\FortifyLoginResponse;
use App\Http\Responses\FortifyRegisterResponse;
use App\Http\Responses\FortifyVerifyEmailResponse;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, FortifyLoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, FortifyRegisterResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, FortifyVerifyEmailResponse::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::query()->whereEmail($request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                if ($user->status === 'suspended') {
                    AuditLogService::log(
                        'login_suspended_user',
                        $user,
                        [],
                        ['ip' => $request->ip()],
                    );

                    if (filled($user->suspended_reason)) {
                        return null;
                    }
                }

                return $user;
            }

            return null;
        });

        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            $attempts = RateLimiter::attempts($throttleKey);
            $maxAttempts = match (true) {
                $attempts >= 10 => 1,
                $attempts >= 5 => 2,
                $attempts >= 3 => 3,
                default => 5,
            };

            return Limit::perMinute($maxAttempts)->by($throttleKey);
        });
    }
}
