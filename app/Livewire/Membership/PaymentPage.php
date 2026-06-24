<?php

namespace App\Livewire\Membership;

use App\Events\PaymentSubmitted;
use App\Models\Setting;
use App\Services\MembershipStateService;
use App\Services\PaystackService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PaymentPage extends Component
{
    use WithFileUploads;

    public $paymentProof;

    public $paymentNotes = '';

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

        if (! in_array($status, ['payment_pending', 'payment_processing', 'payment_failed'], true)) {
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

        if ($status && $status !== $this->paymentStatus) {
            $this->paymentStatus = $status;
        }
    }

    public function redirectToPaystack(PaystackService $paystackService)
    {
        $user = auth()->user();
        $memberProfile = $user->memberProfile;

        if (! $memberProfile || $memberProfile->onboarding_status !== 'payment_pending') {
            Log::warning('PaymentPage: blocked Paystack redirect — wrong status', [
                'user_id' => $user->id,
                'status' => $memberProfile?->onboarding_status,
            ]);

            return;
        }

        $billingCycle = $memberProfile->preferred_billing_cycle ?? 'monthly';

        try {
            app(MembershipStateService::class)->markProcessing($memberProfile, $user);
            $this->paymentStatus = 'payment_processing';

            $url = $paystackService->getAuthorizationUrl($user, $billingCycle);

            Log::info('PaymentPage: redirecting to Paystack', [
                'user_id' => $user->id,
                'profile_id' => $memberProfile->id,
                'billing_cycle' => $billingCycle,
            ]);

            return redirect()->away($url);
        } catch (\Throwable $e) {
            Log::error('PaymentPage: Paystack initialization failed', [
                'user_id' => $user->id,
                'profile_id' => $memberProfile->id,
                'error' => $e->getMessage(),
            ]);
            $this->addError('paystack', 'Could not connect to payment gateway. Please try the manual bank transfer option below.');
        }
    }

    public function submitPayment(): void
    {
        if ($this->submitting) {
            return;
        }

        $this->validate([
            'paymentProof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'paymentNotes' => 'nullable|string|max:1000',
        ]);

        $this->submitting = true;

        try {
            $user = auth()->user();
            $memberProfile = $user->memberProfile;
            $legacyProfile = $user->profile;

            $status = $memberProfile?->onboarding_status ?? $legacyProfile?->membership_status;

            if ($status !== 'payment_pending') {
                Log::warning('PaymentPage: blocked payment attempt — wrong status', [
                    'user_id' => $user->id,
                    'status' => $status,
                ]);
                $this->addError('paymentProof', 'Payment has already been submitted or your application is not in the correct state.');

                return;
            }

            $path = $this->paymentProof->store('payment-proofs', 'public');
            $fileSize = $this->paymentProof->getSize();
            $fileMime = $this->paymentProof->getMimeType();

            Log::info('PaymentPage: proof file stored', [
                'user_id' => $user->id,
                'path' => $path,
                'size' => $fileSize,
                'mime' => $fileMime,
            ]);

            $profile = $memberProfile ?? $legacyProfile;

            if ($memberProfile) {
                app(MembershipStateService::class)->transition($memberProfile, 'payment_processing', $user, [
                    'payment_submitted_at' => now(),
                    'payment_proof_path' => $path,
                ]);

                PaymentSubmitted::dispatch($memberProfile, $user, $path);
            }

            if ($legacyProfile) {
                $legacyProfile->forceFill([
                    'membership_status' => 'payment_processing',
                ])->saveQuietly();
            }

            $this->paymentStatus = 'payment_processing';
            $this->paymentProof = null;
            $this->paymentNotes = '';

            Log::info('PaymentPage: payment submitted', [
                'user_id' => $user->id,
                'profile_type' => $memberProfile ? 'member_profile' : 'user_profile',
                'file_path' => $path,
            ]);

            session()->flash('message', 'Your payment receipt has been submitted. An admin will verify it shortly.');
        } catch (\Throwable $e) {
            Log::error('PaymentPage: payment submission failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            $this->addError('paymentProof', 'An error occurred while submitting your payment. Please try again.');

            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
        } finally {
            $this->submitting = false;
        }
    }

    public function render()
    {
        $user = auth()->user();
        $memberProfile = $user->memberProfile;
        $legacyProfile = $user->profile;

        $profile = $memberProfile ?? $legacyProfile;
        $status = $this->paymentStatus ?: ($memberProfile?->onboarding_status ?? $legacyProfile?->membership_status);

        $bankDetails = Setting::getValue('bank_details', 'Contact us for bank details');

        $billingCycle = $memberProfile?->preferred_billing_cycle ?? 'monthly';
        $amountDue = app(PaystackService::class)->getAmountForBillingCycle($billingCycle);

        return view('livewire.membership.payment-page', [
            'profile' => $profile,
            'memberProfile' => $memberProfile,
            'status' => $status,
            'bankDetails' => $bankDetails,
            'membershipId' => $memberProfile?->membership_id ?? $legacyProfile?->membership_id,
            'billingCycle' => $billingCycle,
            'amountDue' => $amountDue,
        ])->layout('layouts.guest-livewire', [
            'title' => 'Membership Payment',
        ]);
    }
}
