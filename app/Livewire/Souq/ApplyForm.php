<?php

namespace App\Livewire\Souq;

use App\Models\Setting;
use App\Models\SouqListing;
use App\Services\AuditLogService;
use App\Services\BusinessStateService;
use App\Services\CoinsService;
use App\Services\ImageProcessingService;
use App\Services\PaystackService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class ApplyForm extends Component
{
    use WithFileUploads;

    public string $businessName = '';

    public string $category = '';

    public string $description = '';

    public string $contactEmail = '';

    public string $phone = '';

    public string $website = '';

    public string $instagram = '';

    public $logo = null;

    public bool $submitted = false;

    public bool $hasApproved = false;

    public bool $hasPending = false;

    public bool $hasApprovedUnpaid = false;

    public bool $hasBankTransferPending = false;

    public bool $applyCoins = false;

    public string $paymentMethod = 'card';

    public string $bankDetails = '';

    public ?SouqListing $approvedListing = null;

    public ?SouqListing $approvedUnpaidListing = null;

    public function mount(): void
    {
        $this->contactEmail = (string) auth()->user()->email;
        $this->bankDetails = Setting::getValue('bank_details', 'Contact us for bank details');
        $this->syncListingState();
    }

    public function submit(): void
    {
        if ($this->hasApproved || $this->hasPending || $this->hasApprovedUnpaid) {
            return;
        }

        $this->validate([
            'businessName' => ['required', 'max:255'],
            'category' => ['required', 'in:fashion,food_catering,health_beauty,education,services,creative,other'],
            'description' => ['required', 'max:300'],
            'contactEmail' => ['required', 'email'],
            'phone' => ['nullable', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        /* @phpstan-ignore-next-line */
        $path = $this->logo ? app(ImageProcessingService::class)->resizeAndStore($this->logo, 'souq/logos', 400) : null;

        SouqListing::query()->create([
            'user_id' => auth()->id(),
            'business_name' => $this->businessName,
            'category' => $this->category,
            'description' => $this->description,
            'contact_email' => $this->contactEmail,
            'phone' => $this->phone ?: null,
            'website' => $this->website ?: null,
            'instagram' => $this->instagram ?: null,
            'logo_path' => $path,
            'status' => 'pending',
        ]);

        $this->submitted = true;
        $this->syncListingState();
    }

    public function checkPaymentStatus(): void
    {
        $this->syncListingState();

        if ($this->hasApproved) {
            return;
        }

        if (! $this->approvedUnpaidListing || ! $this->approvedUnpaidListing->paystack_reference) {
            return;
        }

        try {
            $verifiedData = app(PaystackService::class)->verifyPayment(
                $this->approvedUnpaidListing->paystack_reference
            );

            $expectedAmount = (int) Setting::get('souq_listing_fee_kobo');
            $paidAmount = (int) ($verifiedData['amount'] ?? 0);

            $verifiedMetadata = $verifiedData['metadata'] ?? [];
            if (($verifiedMetadata['redemption_applied'] ?? false) && ($verifiedMetadata['coins_used'] ?? 0) > 0) {
                $expectedAmount -= (int) $verifiedMetadata['coins_used'] * CoinsService::coinValueKobo();
            }

            if ($paidAmount < $expectedAmount) {
                return;
            }

            app(BusinessStateService::class)->activate($this->approvedUnpaidListing);

            if (($verifiedMetadata['redemption_applied'] ?? false) && ($verifiedMetadata['coins_used'] ?? 0) > 0) {
                app(CoinsService::class)->applyRedemption(
                    $this->approvedUnpaidListing->owner,
                    (int) $verifiedMetadata['coins_used'],
                    'souq',
                    $this->approvedUnpaidListing->id,
                );
            }

            $this->syncListingState();
        } catch (\Throwable $e) {
            // Payment not yet confirmed — retry on next poll
        }
    }

    public function payListing(PaystackService $paystack)
    {
        if (! $this->approvedUnpaidListing) {
            return;
        }

        $user = auth()->user();
        $feeAmountKobo = (int) Setting::get('souq_listing_fee_kobo');
        $extraMetadata = [];
        $finalAmountKobo = null;

        if ($this->applyCoins) {
            $redemption = CoinsService::calculateMaxDiscount($user, $feeAmountKobo);

            if ($redemption['eligible']) {
                $finalAmountKobo = $redemption['final_amount_kobo'];
                $extraMetadata = [
                    'redemption_applied' => true,
                    'coins_used' => $redemption['coins_to_use'],
                ];
            }
        }

        $url = $paystack->initializeSouqListingPayment($this->approvedUnpaidListing, $finalAmountKobo, $extraMetadata);

        return redirect()->away($url);
    }

    public function submitBankTransfer(): void
    {
        if (! $this->approvedUnpaidListing || $this->approvedUnpaidListing->payment_submitted_at) {
            return;
        }

        $this->approvedUnpaidListing->forceFill([
            'payment_source' => 'bank_transfer',
            'payment_submitted_at' => now(),
        ])->saveQuietly();

        AuditLogService::log(
            action: 'manual_payment_submitted',
            model: $this->approvedUnpaidListing,
            old: ['status' => 'approved_unpaid', 'payment_source' => null],
            new: ['payment_source' => 'bank_transfer', 'payment_submitted_at' => now()],
            targetUserId: auth()->id(),
        );

        $this->hasBankTransferPending = true;
    }

    protected function syncListingState(): void
    {
        $query = SouqListing::query()
            ->where('user_id', auth()->id());

        $this->approvedListing = (clone $query)
            ->whereIn('status', ['approved', 'active'])
            ->first();

        $this->hasApproved = $this->approvedListing !== null;

        $this->approvedUnpaidListing = (clone $query)
            ->where('status', 'approved_unpaid')
            ->first();

        $this->hasApprovedUnpaid = $this->approvedUnpaidListing !== null;
        $this->hasBankTransferPending = $this->approvedUnpaidListing !== null
            && $this->approvedUnpaidListing->payment_source === 'bank_transfer';

        $pending = (clone $query)
            ->where('status', 'pending')
            ->first();

        $this->hasPending = $pending !== null;

        if ($this->hasApproved || $this->hasApprovedUnpaid || $this->hasPending) {
            $this->submitted = false;
        }
    }

    public function render(): View
    {
        $user = auth()->user();
        $feeAmountKobo = (int) Setting::get('souq_listing_fee_kobo');
        $redemption = CoinsService::calculateMaxDiscount($user, $feeAmountKobo);
        $feeAmountNaira = (int) ($feeAmountKobo / 100);

        $finalFeeAmount = $this->applyCoins && $redemption['eligible']
            ? (int) floor($redemption['final_amount_kobo'] / 100)
            : $feeAmountNaira;

        return view('livewire.souq.apply-form', [
            'redemption' => $redemption,
            'feeAmount' => $feeAmountNaira,
            'finalFeeAmount' => $finalFeeAmount,
            'bankDetails' => $this->bankDetails,
        ])->layout('layouts.app', ['title' => 'List Your Business']);
    }
}
