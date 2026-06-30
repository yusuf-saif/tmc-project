<?php

namespace App\Livewire\Souq;

use App\Models\Setting;
use App\Models\SouqListing;
use App\Services\CoinsService;
use App\Services\PaystackService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use App\Services\ImageProcessingService;
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

    public bool $applyCoins = false;

    public ?SouqListing $approvedListing = null;

    public ?SouqListing $approvedUnpaidListing = null;

    public function mount(): void
    {
        $this->contactEmail = (string) auth()->user()->email;
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
        $path = $this->logo ? app(ImageProcessingService::class)->resizeAndStore($this->logo, "souq/logos", 400) : null;

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

    public function payListing(PaystackService $paystack): void
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

        $this->redirect($url);
    }

    protected function syncListingState(): void
    {
        $this->approvedListing = SouqListing::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['approved', 'active'])
            ->first();

        $this->hasApproved = $this->approvedListing !== null;

        $this->approvedUnpaidListing = SouqListing::query()
            ->where('user_id', auth()->id())
            ->where('status', 'approved_unpaid')
            ->first();

        $this->hasApprovedUnpaid = $this->approvedUnpaidListing !== null;

        $pending = SouqListing::query()
            ->where('user_id', auth()->id())
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
        ])->layout('layouts.app', ['title' => 'List Your Business']);
    }
}
