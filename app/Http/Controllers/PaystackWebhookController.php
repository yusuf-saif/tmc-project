<?php

namespace App\Http\Controllers;

use App\Models\JannahCoinsLedger;
use App\Models\MemberProfile;
use App\Models\PaymentRecord;
use App\Models\Setting;
use App\Models\SouqListing;
use App\Services\AuditLogService;
use App\Services\BusinessStateService;
use App\Services\CoinsService;
use App\Services\MembershipStateService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackService $paystackService): JsonResponse
    {
        if (! config('payments.enabled', true)) {
            Log::info('PaystackWebhook: payments disabled, returning 200');

            return response()->json(['status' => 'payments_disabled']);
        }

        $payload = $request->all();
        $signature = $request->header('x-paystack-signature');
        $webhookSecret = config('paystack.webhookSecret');

        Log::info('PaystackWebhook: received', [
            'event' => $payload['event'] ?? 'unknown',
            'has_signature' => $signature !== null,
        ]);

        if (! $webhookSecret) {
            Log::critical('PaystackWebhook: webhook secret is not configured');

            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        if (! $signature) {
            Log::warning('PaystackWebhook: missing signature header');

            return response()->json(['error' => 'Missing signature'], 400);
        }

        $expected = hash_hmac('sha512', $request->getContent(), $webhookSecret);
        if (! hash_equals($expected, $signature)) {
            Log::warning('PaystackWebhook: invalid signature');

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('PaystackWebhook: signature verified');

        $event = $payload['event'] ?? '';

        if ($event !== 'charge.success') {
            Log::info('PaystackWebhook: non-charge.success event ignored', ['event' => $event]);

            return response()->json(['status' => 'ignored']);
        }

        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? null;
        $metadata = $data['metadata'] ?? [];
        $paymentType = $metadata['payment_type'] ?? 'membership_payment';

        Log::info('PaystackWebhook: processing charge.success', [
            'reference' => $reference,
            'payment_type' => $paymentType,
        ]);

        if (! $reference) {
            Log::warning('PaystackWebhook: no reference in payload');

            return response()->json(['error' => 'Missing reference'], 400);
        }

        if ($paymentType === 'souq_listing_fee') {
            return $this->handleSouqPayment($data, $reference, $metadata, $paystackService);
        }

        // === Membership payment flow ===

        $paymentRecord = PaymentRecord::query()
            ->where('external_reference', $reference)
            ->first();

        $profile = $paymentRecord?->memberProfile
            ?? ($paymentRecord?->member_profile_id ? MemberProfile::find($paymentRecord->member_profile_id) : null)
            ?? MemberProfile::where('paystack_reference', $reference)->first();

        if (! $profile) {
            Log::warning('PaystackWebhook: no member profile found for reference', [
                'reference' => $reference,
            ]);

            return response()->json(['status' => 'no_profile']);
        }

        Log::info('PaystackWebhook: profile found by reference', [
            'profile_id' => $profile->id,
            'user_id' => $profile->user_id,
            'onboarding_status' => $profile->onboarding_status,
            'payment_verified_at' => $profile->payment_verified_at,
        ]);

        if ($paymentRecord && $paymentRecord->status === 'paid') {
            Log::info('PaystackWebhook: payment record already paid, skipping (idempotent)', [
                'profile_id' => $profile->id,
                'reference' => $reference,
                'record_id' => $paymentRecord->id,
            ]);

            $this->applyMembershipRedemption($profile, $metadata);

            return response()->json(['status' => 'already_verified']);
        }

        $user = $profile->user;

        if ($user && $user->status === 'suspended' && filled($user->suspended_reason)) {
            Log::warning('PaystackWebhook: member manually suspended, payment rejected', [
                'profile_id' => $profile->id,
                'user_id' => $user->id,
                'reference' => $reference,
            ]);

            return response()->json(['error' => 'Membership suspended by admin'], 400);
        }

        $allowedForWebhook = ['onboarding', 'active', 'member', 'suspended'];

        if (! in_array($profile->onboarding_status, $allowedForWebhook, true)) {
            Log::warning('PaystackWebhook: profile not in approvable state', [
                'profile_id' => $profile->id,
                'status' => $profile->onboarding_status,
                'expected' => implode(', ', $allowedForWebhook),
            ]);

            return response()->json(['error' => 'Profile not in approvable state'], 400);
        }

        try {
            $verifiedData = $paystackService->verifyPayment($reference);

            $billingCycle = $paymentRecord?->billing_cycle
                ?? ($metadata['billing_cycle'] ?? null)
                ?? $profile->preferred_billing_cycle
                ?? 'monthly';
            $expectedAmount = $paystackService->getAmountForBillingCycle($billingCycle) * 100;

            if (($metadata['redemption_applied'] ?? false) && ($metadata['coins_used'] ?? 0) > 0) {
                $expectedAmount -= (int) $metadata['coins_used'] * CoinsService::coinValueKobo();
            }

            $paidAmount = (int) ($verifiedData['amount'] ?? 0);

            Log::info('PaystackWebhook: amount check', [
                'billing_cycle' => $billingCycle,
                'expected' => $expectedAmount,
                'paid' => $paidAmount,
            ]);

            $paymentRecord ??= app(MembershipStateService::class)
                ->findOrCreatePaymentRecord($profile->user, $profile, $reference, 'paystack', $billingCycle);

            $paymentRecord->forceFill([
                'provider' => 'paystack',
                'channel' => $verifiedData['channel'] ?? $data['channel'] ?? $paymentRecord->channel,
                'amount_kobo' => $paidAmount ?: $paymentRecord->amount_kobo,
                'currency' => $verifiedData['currency'] ?? 'NGN',
            ])->saveQuietly();

            if ($paidAmount < $expectedAmount) {
                Log::warning('PaystackWebhook: payment amount mismatch', [
                    'reference' => $reference,
                    'billing_cycle' => $billingCycle,
                    'expected' => $expectedAmount,
                    'paid' => $paidAmount,
                ]);

                $paymentRecord->forceFill([
                    'status' => 'failed',
                    'failure_reason' => "Paid {$paidAmount} instead of {$expectedAmount}",
                ])->saveQuietly();

                AuditLogService::log(
                    action: 'paystack_amount_mismatch',
                    model: $profile,
                    old: ['onboarding_status' => $profile->onboarding_status],
                    new: ['onboarding_status' => $profile->onboarding_status, 'reason' => "Paid {$paidAmount} instead of {$expectedAmount}"],
                    targetUserId: $profile->user_id,
                );

                return response()->json(['error' => 'Payment amount does not match billing cycle'], 400);
            }

            app(MembershipStateService::class)->recordPayment($profile, $profile->user, $billingCycle, $paymentRecord);

            $this->applyMembershipRedemption($profile, $metadata);

            $profile->refresh();

            AuditLogService::log(
                action: 'paystack_webhook_activated',
                model: $profile,
                old: ['onboarding_status' => 'onboarding'],
                new: ['onboarding_status' => $profile->onboarding_status, 'membership_id' => $profile->membership_id],
                targetUserId: $profile->user_id,
            );

            Log::info('PaystackWebhook: membership activated successfully', [
                'user_id' => $profile->user_id,
                'reference' => $reference,
                'plan_label' => $billingCycle,
                'current_period_ends_at' => $profile->current_period_ends_at?->toIso8601String(),
            ]);

            return response()->json(['status' => 'activated']);
        } catch (\Throwable $e) {
            Log::error('PaystackWebhook: activation failed', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);

            try {
                $profile->saveQuietly();
            } catch (\Throwable $inner) {
                Log::warning('PaystackWebhook: could not save profile after error', ['error' => $inner->getMessage()]);
            }

            return response()->json(['error' => 'Activation failed'], 500);
        }
    }

    protected function applyMembershipRedemption(MemberProfile $profile, array $metadata): void
    {
        if (! ($metadata['redemption_applied'] ?? false) || (int) ($metadata['coins_used'] ?? 0) <= 0) {
            return;
        }

        $alreadyRedeemed = JannahCoinsLedger::query()
            ->where('user_id', $profile->user_id)
            ->where('reason', 'redemption_membership')
            ->where('reference_id', $profile->id)
            ->exists();

        if (! $alreadyRedeemed) {
            app(CoinsService::class)->applyRedemption(
                $profile->user,
                (int) $metadata['coins_used'],
                'membership',
                $profile->id,
            );
        }
    }

    protected function applySouqRedemption(SouqListing $listing, array $metadata): void
    {
        if (! ($metadata['redemption_applied'] ?? false) || (int) ($metadata['coins_used'] ?? 0) <= 0) {
            return;
        }

        $alreadyRedeemed = JannahCoinsLedger::query()
            ->where('user_id', $listing->user_id)
            ->where('reason', 'redemption_souq')
            ->where('reference_id', $listing->id)
            ->exists();

        if (! $alreadyRedeemed) {
            app(CoinsService::class)->applyRedemption(
                $listing->owner,
                (int) $metadata['coins_used'],
                'souq',
                $listing->id,
            );
        }
    }

    protected function handleSouqPayment(
        array $data,
        string $reference,
        array $metadata,
        PaystackService $paystackService,
    ): JsonResponse {
        $listing = SouqListing::where('paystack_reference', $reference)->first();

        if (! $listing) {
            Log::warning('PaystackWebhook: no Souq listing found for reference', [
                'reference' => $reference,
            ]);

            return response()->json(['status' => 'no_listing']);
        }

        Log::info('PaystackWebhook: Souq listing found by reference', [
            'listing_id' => $listing->id,
            'business_name' => $listing->business_name,
            'status' => $listing->status,
        ]);

        if ($listing->status === 'active') {
            Log::info('PaystackWebhook: Souq listing already active, skipping (idempotent)', [
                'listing_id' => $listing->id,
                'reference' => $reference,
            ]);

            $this->applySouqRedemption($listing, $metadata);

            return response()->json(['status' => 'already_active']);
        }

        if ($listing->status !== 'approved_unpaid') {
            Log::warning('PaystackWebhook: Souq listing not in payable state', [
                'listing_id' => $listing->id,
                'status' => $listing->status,
                'expected' => 'approved_unpaid',
            ]);

            return response()->json(['error' => 'Listing not in payable state'], 400);
        }

        try {
            $verifiedData = $paystackService->verifyPayment($reference);

            $expectedAmount = (int) Setting::get('souq_listing_fee_kobo');

            if (($metadata['redemption_applied'] ?? false) && ($metadata['coins_used'] ?? 0) > 0) {
                $expectedAmount -= (int) $metadata['coins_used'] * CoinsService::coinValueKobo();
            }

            $paidAmount = (int) ($verifiedData['amount'] ?? 0);

            Log::info('PaystackWebhook: Souq amount check', [
                'listing_id' => $listing->id,
                'expected' => $expectedAmount,
                'paid' => $paidAmount,
            ]);

            if ($paidAmount < $expectedAmount) {
                Log::warning('PaystackWebhook: Souq payment amount mismatch', [
                    'reference' => $reference,
                    'listing_id' => $listing->id,
                    'expected' => $expectedAmount,
                    'paid' => $paidAmount,
                ]);

                AuditLogService::log(
                    action: 'paystack_amount_mismatch',
                    model: $listing,
                    old: ['status' => $listing->status],
                    new: ['status' => $listing->status, 'reason' => "Paid {$paidAmount} instead of {$expectedAmount}"],
                    targetUserId: $listing->user_id,
                );

                return response()->json(['error' => 'Payment amount does not match listing fee'], 400);
            }

            app(BusinessStateService::class)->activate($listing);

            $this->applySouqRedemption($listing, $metadata);

            $listing->refresh();

            Log::info('PaystackWebhook: Souq listing activated successfully', [
                'listing_id' => $listing->id,
                'reference' => $reference,
                'billing_end_date' => $listing->billing_end_date?->toIso8601String(),
            ]);

            return response()->json(['status' => 'activated']);
        } catch (\Throwable $e) {
            Log::error('PaystackWebhook: Souq activation failed', [
                'listing_id' => $listing->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Activation failed'], 500);
        }
    }
}
