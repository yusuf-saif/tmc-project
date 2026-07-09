<?php

namespace App\Services;

use App\Models\MemberProfile;
use App\Models\Setting;
use App\Models\SouqListing;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $secretKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('paystack.secretKey') ?? env('PAYSTACK_SECRET_KEY', '');
        $this->baseUrl = config('paystack.paymentUrl', 'https://api.paystack.co');

        if (empty($this->secretKey)) {
            throw new \RuntimeException('Paystack secret key is not configured. Set PAYSTACK_SECRET_KEY in .env.');
        }
    }

    public function getAmountForBillingCycle(string $cycle): int
    {
        return match ($cycle) {
            'quarterly' => (int) Setting::get('membership_fee_quarterly'),
            'yearly' => (int) Setting::get('membership_fee_yearly'),
            default => (int) Setting::get('membership_fee_monthly'),
        };
    }

    public function initializePayment(User $user, string $billingCycle, string $reference, ?int $finalAmountKobo = null, array $extraMetadata = []): array
    {
        $amount = $finalAmountKobo ?? $this->getAmountForBillingCycle($billingCycle) * 100;

        Log::info('PaystackService: initializing payment', [
            'user_id' => $user->id,
            'billing_cycle' => $billingCycle,
            'reference' => $reference,
            'amount' => $amount,
        ]);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->secretKey}",
            'Content-Type' => 'application/json',
        ])->timeout(15)
            ->connectTimeout(5)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'amount' => $amount,
                'email' => $user->email,
                'reference' => $reference,
                'callback_url' => route('membership.payment'),
                'metadata' => array_merge([
                    'user_id' => $user->id,
                    'billing_cycle' => $billingCycle,
                    'membership_type' => 'membership_payment',
                ], $extraMetadata),
            ]);

        $body = $response->json();

        if (! $response->successful() || empty($body['data']['authorization_url'])) {
            Log::error('PaystackService: initialization failed', [
                'user_id' => $user->id,
                'http_status' => $response->status(),
                'message' => $body['message'] ?? 'Unknown error',
            ]);
            throw new \RuntimeException('Payment initialization failed. Please try again.');
        }

        Log::info('PaystackService: payment initialized successfully', [
            'user_id' => $user->id,
            'reference' => $reference,
            'authorization_url' => $body['data']['authorization_url'] ?? null,
        ]);

        return $body['data'];
    }

    public function verifyPayment(string $reference): array
    {
        Log::info('PaystackService: verifying payment', [
            'reference' => $reference,
        ]);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->secretKey}",
            'Content-Type' => 'application/json',
        ])->timeout(15)
            ->connectTimeout(5)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        $body = $response->json();
        $paidStatus = $body['data']['status'] ?? 'unknown';
        $paidAmount = $body['data']['amount'] ?? 0;

        if (! $response->successful() || $paidStatus !== 'success') {
            Log::warning('PaystackService: verification failed', [
                'reference' => $reference,
                'http_status' => $response->status(),
                'paystack_status' => $paidStatus,
                'paid_amount' => $paidAmount,
            ]);
            throw new \RuntimeException('Payment verification failed.');
        }

        Log::info('PaystackService: payment verified successfully', [
            'reference' => $reference,
            'amount' => $paidAmount,
            'status' => $paidStatus,
        ]);

        return $body['data'];
    }

    public function generateReference(): string
    {
        return 'TMC-'.strtoupper(uniqid());
    }

    public function getAuthorizationUrl(User $user, string $billingCycle, ?int $finalAmountKobo = null, array $extraMetadata = []): string
    {
        $reference = $this->generateReference();

        $data = $this->initializePayment($user, $billingCycle, $reference, $finalAmountKobo, $extraMetadata);

        MemberProfile::where('user_id', $user->id)->update([
            'paystack_reference' => $reference,
        ]);

        Log::info('PaystackService: authorization URL generated', [
            'user_id' => $user->id,
            'reference' => $reference,
        ]);

        return $data['authorization_url'];
    }

    public function initializeSouqListingPayment(SouqListing $listing, ?int $finalAmountKobo = null, array $extraMetadata = []): string
    {
        $amountKobo = $finalAmountKobo ?? (int) Setting::get('souq_listing_fee_kobo');
        $reference = $this->generateReference();

        Log::info('PaystackService: initializing Souq listing payment', [
            'listing_id' => $listing->id,
            'reference' => $reference,
            'amount_kobo' => $amountKobo,
        ]);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->secretKey}",
            'Content-Type' => 'application/json',
        ])->timeout(15)
            ->connectTimeout(5)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'amount' => $amountKobo,
                'email' => $listing->owner->email,
                'reference' => $reference,
                'callback_url' => route('souq.apply'),
                'metadata' => array_merge([
                    'user_id' => $listing->owner->id,
                    'payment_type' => 'souq_listing_fee',
                    'listing_id' => $listing->id,
                ], $extraMetadata),
            ]);

        $body = $response->json();

        if (! $response->successful() || empty($body['data']['authorization_url'])) {
            Log::error('PaystackService: Souq payment initialization failed', [
                'listing_id' => $listing->id,
                'http_status' => $response->status(),
                'message' => $body['message'] ?? 'Unknown error',
            ]);
            throw new \RuntimeException('Payment initialization failed. Please try again.');
        }

        $listing->update(['paystack_reference' => $reference]);

        Log::info('PaystackService: Souq payment initialized successfully', [
            'listing_id' => $listing->id,
            'reference' => $reference,
            'authorization_url' => $body['data']['authorization_url'] ?? null,
        ]);

        return $body['data']['authorization_url'];
    }
}
