<?php

namespace App\Services;

use App\Models\MemberProfile;
use App\Models\Setting;
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
        return (int) match ($cycle) {
            'quarterly' => Setting::getValue('membership_fee_quarterly', '12000'),
            'yearly' => Setting::getValue('membership_fee_yearly', '40000'),
            default => Setting::getValue('membership_fee_monthly', '5000'),
        };
    }

    public function initializePayment(User $user, string $billingCycle, string $reference): array
    {
        $amount = $this->getAmountForBillingCycle($billingCycle);

        Log::info('PaystackService: initializing payment', [
            'user_id' => $user->id,
            'billing_cycle' => $billingCycle,
            'reference' => $reference,
            'amount' => $amount,
        ]);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->secretKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/transaction/initialize", [
            'amount' => $amount * 100,
            'email' => $user->email,
            'reference' => $reference,
            'callback_url' => route('membership.payment'),
            'metadata' => [
                'user_id' => $user->id,
                'billing_cycle' => $billingCycle,
                'membership_type' => 'membership_payment',
            ],
        ]);

        $body = $response->json();

        if (! $response->successful() || empty($body['data']['authorization_url'])) {
            Log::error('PaystackService: initialization failed', [
                'user_id' => $user->id,
                'http_status' => $response->status(),
                'response' => $body,
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
        ])->get("{$this->baseUrl}/transaction/verify/{$reference}");

        $body = $response->json();
        $paidStatus = $body['data']['status'] ?? 'unknown';
        $paidAmount = $body['data']['amount'] ?? 0;

        if (! $response->successful() || $paidStatus !== 'success') {
            Log::warning('PaystackService: verification failed', [
                'reference' => $reference,
                'http_status' => $response->status(),
                'paystack_status' => $paidStatus,
                'paid_amount' => $paidAmount,
                'response' => $body,
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

    public function getAuthorizationUrl(User $user, string $billingCycle): string
    {
        $reference = $this->generateReference();

        $data = $this->initializePayment($user, $billingCycle, $reference);

        MemberProfile::where('user_id', $user->id)->update([
            'paystack_reference' => $reference,
        ]);

        Log::info('PaystackService: authorization URL generated', [
            'user_id' => $user->id,
            'reference' => $reference,
        ]);

        return $data['authorization_url'];
    }
}
