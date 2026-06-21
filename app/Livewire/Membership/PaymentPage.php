<?php

namespace App\Livewire\Membership;

use App\Events\PaymentSubmitted;
use App\Models\MemberProfile;
use App\Models\Setting;
use App\Services\MembershipStateService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentPage extends Component
{
    use WithFileUploads;

    public $paymentProof;
    public $paymentNotes = '';
    public bool $submitting = false;
    public bool $submitted = false;

    public function mount(): void
    {
        $user = auth()->user();
        $memberProfile = $user->memberProfile;
        $legacyProfile = $user->profile;

        $status = $memberProfile?->onboarding_status ?? $legacyProfile?->membership_status;

        if (! $status || ! in_array($status, ['approved_pending_payment', 'payment_submitted'], true)) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $this->submitted = $status === 'payment_submitted';
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

            if ($status !== 'approved_pending_payment') {
                Log::warning('Blocked payment attempt — wrong status', [
                    'user_id' => $user->id,
                    'status' => $status,
                ]);
                $this->addError('paymentProof', 'Payment has already been submitted or your application is not in the correct state.');

                return;
            }

            $path = $this->paymentProof->store('payment-proofs', 'public');

            $profile = $memberProfile ?? $legacyProfile;

            if ($memberProfile) {
                app(MembershipStateService::class)->transition($memberProfile, 'payment_submitted', $user, [
                    'payment_submitted_at' => now(),
                    'payment_proof_path' => $path,
                ]);

                PaymentSubmitted::dispatch($memberProfile, $user, $path);
            }

            if ($legacyProfile) {
                $legacyProfile->forceFill([
                    'membership_status' => 'payment_submitted',
                ])->saveQuietly();
            }

            $this->submitted = true;
            $this->paymentProof = null;
            $this->paymentNotes = '';

            Log::info('Payment submitted', [
                'user_id' => $user->id,
                'profile_type' => $memberProfile ? 'member_profile' : 'user_profile',
                'file_path' => $path,
            ]);

            session()->flash('message', 'Your payment receipt has been submitted. An admin will verify it shortly.');
        } catch (\Throwable $e) {
            Log::error('Payment submission failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        $status = $memberProfile?->onboarding_status ?? $legacyProfile?->membership_status;

        $bankDetails = Setting::getValue('bank_details', 'Contact us for bank details');

        return view('livewire.membership.payment-page', [
            'profile' => $profile,
            'memberProfile' => $memberProfile,
            'status' => $status,
            'bankDetails' => $bankDetails,
            'membershipId' => $memberProfile?->membership_id ?? $legacyProfile?->membership_id,
        ])->layout('layouts.guest-livewire', [
            'title' => 'Membership Payment',
        ]);
    }
}
