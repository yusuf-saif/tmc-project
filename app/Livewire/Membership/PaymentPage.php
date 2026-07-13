<?php

namespace App\Livewire\Membership;

use App\Models\JannahCoinsLedger;
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

    public string $paymentMethod = 'card';

    public string $bankDetails = '';

    public function mount()
    {
        $this->loadBillingOptions();
        $this->bankDetails = Setting::getValue('bank_details', 'Contact us for bank details');
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

        if (! $memberProfile) {
            $this->redirect(route('membership.signup'));

            return;
        }

        $status = $memberProfile->onboarding_status;

        $allowedStatuses = ['onboarding', 'active', 'suspended'];

        if ($status === 'member') {
            $this->redirect(route('home'));

            return;
        }

        if ($memberProfile->payment_status === 'pending_verification') {
            $this->paymentStatus = 'pending_verification';

            return;
        }

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

        if (! $profile) {
            return;
        }

        if ($profile->onboarding_status === 'member') {
            $this->redirect(route('home'));

            return;
        }

        $allowedForVerification = ['onboarding', 'active', 'suspended'];

        if (config('payments.enabled', true) && in_array($profile->onboarding_status, $allowedForVerification) && $profile->paystack_reference && $profile->payment_verified_at === null) {
            $this->verifyPaymentWithPaystack($profile, $user);
        }

        $profile->refresh();

        if ($profile->onboarding_status === 'member') {
            $this->redirect(route('home'));

            return;
        }

        if ($profile->payment_status === 'pending_verification') {
            $this->paymentStatus = 'pending_verification';

            return;
        }

        if ($profile->onboarding_status !== $this->paymentStatus) {
            $this->paymentStatus = $profile->onboarding_status;
        }
    }

    protected function verifyPaymentWithPaystack($profile, $user): void
    {
        if (! config('payments.enabled', true)) {
            return;
        }

        try {
            $paystackService = app(PaystackService::class);
            $verifiedData = $paystackService->verifyPayment($profile->paystack_reference);

            $planLabel = $profile->preferred_billing_cycle ?? 'monthly';
            $expectedAmount = $paystackService->getAmountForBillingCycle($planLabel) * 100;

            $verifiedMetadata = $verifiedData['metadata'] ?? [];
            if (($verifiedMetadata['redemption_applied'] ?? false) && ($verifiedMetadata['coins_used'] ?? 0) > 0) {
                $expectedAmount -= (int) $verifiedMetadata['coins_used'] * CoinsService::coinValueKobo();
            }

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

            if (($verifiedMetadata['redemption_applied'] ?? false) && ($verifiedMetadata['coins_used'] ?? 0) > 0) {
                $alreadyRedeemed = JannahCoinsLedger::query()
                    ->where('user_id', $user->id)
                    ->where('reason', 'redemption_membership')
                    ->where('reference_id', $profile->id)
                    ->exists();

                if (! $alreadyRedeemed) {
                    app(CoinsService::class)->applyRedemption(
                        $user,
                        (int) $verifiedMetadata['coins_used'],
                        'membership',
                        $profile->id,
                    );
                }
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

    public function redirectToPaystack(PaystackService $paystackService)
    {
        if (! config('payments.enabled', true)) {
            session()->flash('message', 'Online payment is temporarily unavailable. Please pay via bank transfer.');
            $this->redirect(route('membership.payment'));

            return;
        }

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

    public function submitBankTransfer(): void
    {
        if ($this->submitting) {
            return;
        }

        $user = auth()->user();
        $memberProfile = $user->memberProfile;

        if (! $memberProfile || ! in_array($memberProfile->onboarding_status, ['active', 'onboarding', 'suspended'], true)) {
            return;
        }

        $this->submitting = true;

        $memberProfile->forceFill([
            'preferred_billing_cycle' => $this->billingCycle,
            'payment_source' => 'bank_transfer',
            'payment_status' => 'pending_verification',
            'payment_submitted_at' => now(),
        ])->saveQuietly();

        AuditLogService::log(
            action: 'manual_payment_submitted',
            model: $memberProfile,
            old: ['onboarding_status' => $memberProfile->onboarding_status, 'payment_status' => $memberProfile->payment_status],
            new: ['payment_source' => 'bank_transfer', 'payment_status' => 'pending_verification', 'payment_submitted_at' => now()],
            targetUserId: $user->id,
        );

        Log::info('PaymentPage: manual payment submitted', [
            'user_id' => $user->id,
            'profile_id' => $memberProfile->id,
            'billing_cycle' => $this->billingCycle,
        ]);

        $this->paymentStatus = 'pending_verification';
    }

    public function render()
    {
        $user = auth()->user();
        $memberProfile = $user->memberProfile;

        $profile = $memberProfile;

        if ($memberProfile && $memberProfile->payment_status === 'pending_verification') {
            $this->paymentStatus = 'pending_verification';
        }

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
            'bankDetails' => $this->bankDetails,
            'paymentsEnabled' => config('payments.enabled', true),
        ])->layout('layouts.guest-livewire', [
            'title' => 'Membership Payment',
        ]);
    }
}
