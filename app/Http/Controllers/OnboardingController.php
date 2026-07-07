<?php

namespace App\Http\Controllers;

use App\Events\MemberOnboardingCompleted;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function showForm(Request $request): View|RedirectResponse
    {
        $token = $request->query('token');
        $email = $request->query('email');
        $memberId = $request->query('member_id');

        if (! $token || ! $email || ! $memberId) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid onboarding link.']);
        }

        $user = User::where('email', $email)->first();

        if (! $user || $user->member_id !== $memberId) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid onboarding link.']);
        }

        if ($user->status !== 'pending_onboarding') {
            return redirect()->route('login')->withErrors(['email' => 'This account has already been activated.']);
        }

        if (! Password::broker()->tokenExists($user, $token)) {
            return redirect()->route('login')->withErrors(['email' => 'This onboarding link has expired. Please request a new one.']);
        }

        return view('onboarding.form', [
            'token' => $token,
            'email' => $email,
            'memberId' => $memberId,
            'name' => $user->name,
            'nickname' => $user->memberProfile?->display_name ?? $user->name,
            'interests' => Interest::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'goals' => Goal::query()->where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $token = $request->input('token');
        $email = $request->input('email');
        $memberId = $request->input('member_id');

        if (! $token || ! $email || ! $memberId) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid onboarding link.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', PasswordRule::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
            'location_country' => ['required', 'string', 'max:255'],
            'location_state' => [Rule::requiredIf(fn () => $request->input('location_country') === 'Nigeria'), 'nullable', 'string', 'max:255'],
            'location_international' => [Rule::requiredIf(fn () => $request->input('location_country') !== 'Nigeria'), 'nullable', 'string', 'max:500'],
            'age_group' => ['required', 'string', 'max:50'],
            'marital_status' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
            'ig_username' => ['nullable', 'string', 'max:255'],
            'fb_username' => ['nullable', 'string', 'max:255'],
            'x_username' => ['nullable', 'string', 'max:255'],
            'tiktok_username' => ['nullable', 'string', 'max:255'],
            'interests' => ['required', 'array', 'min:1', 'max:5'],
            'interests.*' => ['string', 'exists:interests,slug'],
            'goals' => ['required', 'array', 'min:1'],
            'goals.*' => ['string', 'exists:goals,slug'],
        ]);

        $user = User::where('email', $email)->first();

        if (! $user || $user->member_id !== $memberId) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid onboarding link.']);
        }

        if ($user->status !== 'pending_onboarding') {
            return redirect()->route('login')->withErrors(['email' => 'This account has already been activated.']);
        }

        if (! Password::broker()->tokenExists($user, $token)) {
            return redirect()->route('login')->withErrors(['email' => 'This onboarding link has expired. Please request a new one.']);
        }

        try {
            DB::transaction(function () use ($user, $validated) {
                $user->update([
                    'password' => Hash::make($validated['password']),
                    'status' => 'active',
                ]);

                if (! $user->referral_code) {
                    $user->forceFill(['referral_code' => User::generateUniqueReferralCode()])->save();
                }
                $user->forceFill(['email_verified_at' => now()])->save();

                $profile = $user->memberProfile;
                if ($profile) {
                    $profile->update([
                        'location_country' => $validated['location_country'],
                        'location_state' => $validated['location_state'] ?? null,
                        'location_international' => $validated['location_international'] ?? null,
                        'age_group' => $validated['age_group'],
                        'marital_status' => $validated['marital_status'],
                        'phone' => $validated['phone'],
                        'ig_username' => $validated['ig_username'] ?? null,
                        'fb_username' => $validated['fb_username'] ?? null,
                        'x_username' => $validated['x_username'] ?? null,
                        'tiktok_username' => $validated['tiktok_username'] ?? null,
                        'onboarding_status' => 'active',
                        'onboarding_completed_at' => now(),
                    ]);
                }

                $interestIds = Interest::whereIn('slug', $validated['interests'])->pluck('id');
                $user->interests()->sync($interestIds);

                $goalIds = Goal::whereIn('slug', $validated['goals'])->pluck('id');
                $user->goals()->sync($goalIds);
            });

            Password::broker()->deleteToken($user);

            MemberOnboardingCompleted::dispatch($user, $memberId);

            Auth::login($user);

            return redirect()->intended(route('home'));
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['email' => 'Something went wrong. Please try again.']);
        }
    }
}
