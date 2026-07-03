<?php

namespace App\Livewire\Membership;

use App\Models\Setting;
use App\Services\AuditLogService;
use App\Services\CoinsService;
use App\Services\MembershipStateService;
use App\Services\PaystackService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class PaymentPage extends Component
{
    public bool $submitting = false;

    public string $paymentStatus = '';

    public string $billingCycle = 'monthly';

    public array $billingOptions = [];

    public bool $applyCoins = false;

    public function mount()
    {
        $this->loadBillingOptions();

        $this->refreshStatus();
    }

    protected function loadBillingOptions(): void
    {
        $this->billingOptions = [
            'monthly' => [
                'label' => 'Monthly',
                'price' => (int) Setting::get('membership_fee_monthly'),
                'interval' => 'per month',
            ],
            'quarterly' => [
                'label' => 'Quarterly',
                'price' => (int) Setting::get('membership_fee_quarterly'),
                'interval' => 'per quarter',
            ],
            'yearly' => [
                'label' => 'Yearly',
                'price' => (int) Setting::get('membership_fee_yearly'),
                'interval' => 'per year',
            ],
        ];
    }

    public function selectBillingCycle(string $cycle): void
    {
        if (! in_array($cycle, ['monthly', 'quarterly', 'yearly'], true)) {
            return;
        }

        $this->billingCycle = $cycle;

        $profile = auth()->user()->memberProfile;
        if ($profile) {
            $profile->forceFill(['preferred_billing_cycle' => $cycle])->saveQuietly();
        }
    }

    public function refreshStatus(): void
    {
        $user = auth()->user();
        $user->refresh();

        $memberProfile = $user->memberProfile;

        $status = $memberProfile?->onboarding_status;

        if (! $status) {
            $this->redirect(route('membership.signup'));

            return;
        }

        $allowedStatuses = ['onboarding', 'active', 'suspended'];

        if (! in_array($status, $allowedStatuses, true)) {
            $this->redirect(route('home'));

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

        $allowedForVerification = ['onboarding', 'active', 'suspended'];

        if ($status === 'member') {
            $this->redirect(route('home'));

            return;
        }

        if (in_array($status, $allowedForVerification) && $profile->paystack_reference && $profile->payment_verified_at === null) {
            if (config('paystack.skipVerification', false)) {
                $this->activateWithoutVerification($profile, $user);
            } else {
                $this->verifyPaymentWithPaystack($profile, $user);
            }
        }

        $profile->refresh();
        $updatedStatus = $profile->onboarding_status;

        if ($updatedStatus === 'member') {
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

            $planLabel = $profile->preferred_billing_cycle ?? 'monthly';
            $expectedAmount = $paystackService->getAmountForBillingCycle($planLabel) * 100;
            $paidAmount = (int) ($verifiedData['amount'] ?? 0);

            if ($paidAmount < $expectedAmount) {
                Log::warning('PaymentPage: payment amount mismatch', [
                    'user_id' => $user->id,
                    'reference' => $profile->paystack_reference,
                    'expected' => $expectedAmount,
                    'paid' => $paidAmount,
                ]);

                $profile->forceFill([
                    'payment_failed_reason' => "Paid {$paidAmount} instead of {$expectedAmount}",
                ])->saveQuietly();

                return;
            }

            app(MembershipStateService::class)->recordPayment($profile, $user, $planLabel);

            $profile->refresh();

            AuditLogService::log(
                action: 'payment_verified_polling',
                model: $profile,
                old: ['onboarding_status' => $profile->onboarding_status],
                new: ['onboarding_status' => $profile->onboarding_status, 'membership_id' => $profile->membership_id],
                targetUserId: $user->id,
            );

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

    protected function activateWithoutVerification($profile, $user): void
    {
        try {
            $planLabel = $profile->preferred_billing_cycle ?? 'monthly';

            app(MembershipStateService::class)->recordPayment($profile, $user, $planLabel);

            $profile->refresh();

            AuditLogService::log(
                action: 'payment_verified_bypass',
                model: $profile,
                old: ['onboarding_status' => $profile->onboarding_status],
                new: ['onboarding_status' => $profile->onboarding_status, 'membership_id' => $profile->membership_id],
                targetUserId: $user->id,
            );

            Log::info('PaymentPage: payment bypassed (skip verification)', [
                'user_id' => $user->id,
                'reference' => $profile->paystack_reference,
            ]);
        } catch (\Throwable $e) {
            Log::error('PaymentPage: bypass activation failed', [
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

        $allowedForRedirect = ['onboarding', 'active', 'suspended'];

        if (! $memberProfile || ! in_array($memberProfile->onboarding_status, $allowedForRedirect, true)) {
            Log::warning('PaymentPage: blocked Paystack redirect — wrong status', [
                'user_id' => $user->id,
                'status' => $memberProfile?->onboarding_status,
            ]);

            return;
        }

        $this->submitting = true;

        $billingCycle = $this->billingCycle;

        $memberProfile->forceFill(['preferred_billing_cycle' => $billingCycle])->saveQuietly();

        try {
            $this->paymentStatus = 'onboarding';

            $tierAmountKobo = $paystackService->getAmountForBillingCycle($billingCycle) * 100;
            $extraMetadata = [];
            $finalAmountKobo = null;

            if ($this->applyCoins) {
                $redemption = CoinsService::calculateMaxDiscount($user, $tierAmountKobo);

                if ($redemption['eligible']) {
                    $finalAmountKobo = $redemption['final_amount_kobo'];
                    $extraMetadata = [
                        'redemption_applied' => true,
                        'coins_used' => $redemption['coins_to_use'],
                    ];
                }
            }

            $url = $paystackService->getAuthorizationUrl($user, $billingCycle, $finalAmountKobo, $extraMetadata);

            Log::info('PaymentPage: redirecting to Paystack', [
                'user_id' => $user->id,
                'profile_id' => $memberProfile->id,
                'billing_cycle' => $billingCycle,
                'final_amount_kobo' => $finalAmountKobo ?? $tierAmountKobo,
                'redemption_applied' => $this->applyCoins,
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

        $profile = $memberProfile;
        $status = $this->paymentStatus ?: ($memberProfile?->onboarding_status);

        $billingCycle = $this->billingCycle;
        $amountDue = app(PaystackService::class)->getAmountForBillingCycle($billingCycle);

        $tierAmountKobo = $amountDue * 100;
        $redemption = CoinsService::calculateMaxDiscount($user, $tierAmountKobo);

        $finalAmountDue = $this->applyCoins && $redemption['eligible']
            ? (int) floor($redemption['final_amount_kobo'] / 100)
            : $amountDue;

        return view('livewire.membership.payment-page', [
            'profile' => $profile,
            'memberProfile' => $memberProfile,
            'status' => $status,
            'membershipId' => $memberProfile?->membership_id,
            'billingCycle' => $billingCycle,
            'amountDue' => $amountDue,
            'redemption' => $redemption,
            'finalAmountDue' => $finalAmountDue,
        ])->layout('layouts.guest-livewire', [
            'title' => 'Membership Payment',
        ]);
    }
}
