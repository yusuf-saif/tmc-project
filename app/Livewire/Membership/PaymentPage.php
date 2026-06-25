<?php

namespace App\Livewire\Membership;

use App\Events\MembershipActivated;
use App\Models\MemberProfile;
use App\Services\AuditLogService;
use App\Services\PaystackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class PaymentPage extends Component
{
    public bool $submitting = false;

    public string $paymentStatus = '';

    public function mount()
    {
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $user = auth()->user();
        $user->refresh();

        $memberProfile = $user->memberProfile;
        $legacyProfile = $user->profile;

        $status = $memberProfile?->onboarding_status ?? $legacyProfile?->membership_status;

        if (! $status) {
            redirect()->route('membership.signup');

            return;
        }

        if ($status === 'active') {
            redirect()->route('home');

            return;
        }

        if (! in_array($status, ['approved_pending_payment', 'payment_processing', 'payment_failed'], true)) {
            redirect()->route('home');

            return;
        }

        $this->paymentStatus = $status;
    }

    public function checkPaymentStatus(): void
    {
        $user = auth()->user();
        $user->refresh();

        $profile = $user->memberProfile;
        $status = $profile?->onboarding_status;

        if ($status === 'active') {
            $this->redirect(route('home'));

            return;
        }

        if ($status === 'payment_processing' && $profile->paystack_reference && $profile->payment_verified_at === null) {
            $this->verifyPaymentWithPaystack($profile, $user);
        }

        $profile->refresh();
        $updatedStatus = $profile->onboarding_status;

        if ($updatedStatus === 'active') {
            $this->redirect(route('home'));

            return;
        }

        if ($updatedStatus !== $this->paymentStatus) {
            $this->paymentStatus = $updatedStatus;
        }
    }

    protected function verifyPaymentWithPaystack($profile, $user): void
    {
        try {
            $paystackService = app(PaystackService::class);
            $verifiedData = $paystackService->verifyPayment($profile->paystack_reference);

            $billingCycle = $profile->preferred_billing_cycle ?? 'monthly';
            $expectedAmount = $paystackService->getAmountForBillingCycle($billingCycle) * 100;
            $paidAmount = (int) ($verifiedData['amount'] ?? 0);

            if ($paidAmount < $expectedAmount) {
                Log::warning('PaymentPage: payment amount mismatch', [
                    'user_id' => $user->id,
                    'reference' => $profile->paystack_reference,
                    'expected' => $expectedAmount,
                    'paid' => $paidAmount,
                ]);

                $profile->forceFill([
                    'onboarding_status' => 'payment_failed',
                    'payment_failed_reason' => "Paid {$paidAmount} instead of {$expectedAmount}",
                ])->saveQuietly();

                return;
            }

            $nextDue = match ($billingCycle) {
                'quarterly' => now()->addMonths(3),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            };

            DB::transaction(function () use ($profile, $user, $nextDue): void {
                $locked = MemberProfile::where('id', $profile->id)->lockForUpdate()->first();

                if ($locked->payment_verified_at !== null) {
                    return;
                }

                $locked->update([
                    'onboarding_status' => 'active',
                    'payment_verified_at' => now(),
                    'activated_at' => now(),
                    'next_due_at' => $nextDue,
                    'payment_source' => 'paystack',
                ]);

                $user->forceFill(['status' => 'active'])->saveQuietly();

                $legacy = $user->profile;
                if ($legacy) {
                    $legacy->forceFill([
                        'membership_status' => 'active',
                        'payment_status' => 'paid',
                        'membership_fee_paid_at' => now(),
                    ])->saveQuietly();
                }
            });

            $profile->refresh();

            AuditLogService::log(
                action: 'payment_verified_polling',
                model: $profile,
                old: ['onboarding_status' => 'payment_processing'],
                new: ['onboarding_status' => 'active', 'membership_id' => $profile->membership_id],
                targetUserId: $user->id,
            );

            MembershipActivated::dispatch($user, $profile->membership_id ?? 'N/A', $user);

            Log::info('PaymentPage: payment verified via polling', [
                'user_id' => $user->id,
                'reference' => $profile->paystack_reference,
            ]);
        } catch (\Throwable $e) {
            Log::info('PaymentPage: Paystack verification not yet ready', [
                'user_id' => $user->id,
                'reference' => $profile->paystack_reference,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function redirectToPaystack(PaystackService $paystackService)
    {
        if ($this->submitting) {
            return;
        }

        $user = auth()->user();
        $memberProfile = $user->memberProfile;

        if (! $memberProfile || $memberProfile->onboarding_status !== 'approved_pending_payment') {
            Log::warning('PaymentPage: blocked Paystack redirect — wrong status', [
                'user_id' => $user->id,
                'status' => $memberProfile?->onboarding_status,
            ]);

            return;
        }

        $this->submitting = true;

        $billingCycle = $memberProfile->preferred_billing_cycle ?? 'monthly';

        try {
            $memberProfile->forceFill(['onboarding_status' => 'payment_processing'])->saveQuietly();
            $this->paymentStatus = 'payment_processing';

            $url = $paystackService->getAuthorizationUrl($user, $billingCycle);

            Log::info('PaymentPage: redirecting to Paystack', [
                'user_id' => $user->id,
                'profile_id' => $memberProfile->id,
                'billing_cycle' => $billingCycle,
            ]);

            return redirect()->away($url);
        } catch (\Throwable $e) {
            $this->submitting = false;

            Log::error('PaymentPage: Paystack initialization failed', [
                'user_id' => $user->id,
                'profile_id' => $memberProfile->id,
                'error' => $e->getMessage(),
            ]);
            $this->addError('paystack', 'Could not connect to payment gateway. Please try again.');
        }
    }

    public function render()
    {
        $user = auth()->user();
        $memberProfile = $user->memberProfile;
        $legacyProfile = $user->profile;

        $profile = $memberProfile ?? $legacyProfile;
        $status = $this->paymentStatus ?: ($memberProfile?->onboarding_status ?? $legacyProfile?->membership_status);

        $billingCycle = $memberProfile?->preferred_billing_cycle ?? 'monthly';
        $amountDue = app(PaystackService::class)->getAmountForBillingCycle($billingCycle);

        return view('livewire.membership.payment-page', [
            'profile' => $profile,
            'memberProfile' => $memberProfile,
            'status' => $status,
            'membershipId' => $memberProfile?->membership_id ?? $legacyProfile?->membership_id,
            'billingCycle' => $billingCycle,
            'amountDue' => $amountDue,
        ])->layout('layouts.guest-livewire', [
            'title' => 'Membership Payment',
        ]);
    }
}
