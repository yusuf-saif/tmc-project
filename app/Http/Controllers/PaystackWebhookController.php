<?php

namespace App\Http\Controllers;

use App\Models\MemberProfile;
use App\Services\AuditLogService;
use App\Services\MembershipStateService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackService $paystackService): JsonResponse
    {
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

        $expected = hash_hmac('sha512', json_encode($payload), $webhookSecret);
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

        Log::info('PaystackWebhook: processing charge.success', [
            'reference' => $reference,
        ]);

        if (! $reference) {
            Log::warning('PaystackWebhook: no reference in payload');

            return response()->json(['error' => 'Missing reference'], 400);
        }

        // Step 1: Find member by paystack_reference
        $profile = MemberProfile::where('paystack_reference', $reference)->first();

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

        // Step 2: Idempotency check — if already verified, exit safely
        if ($profile->payment_verified_at !== null) {
            Log::info('PaystackWebhook: payment already verified, skipping (idempotent)', [
                'profile_id' => $profile->id,
                'reference' => $reference,
            ]);

            return response()->json(['status' => 'already_verified']);
        }

        // Step 3: Validate status — must be in a state where payment is accepted
        $allowedForWebhook = ['onboarding', 'active', 'suspended'];

        if (! in_array($profile->onboarding_status, $allowedForWebhook, true)) {
            Log::warning('PaystackWebhook: profile not in approvable state', [
                'profile_id' => $profile->id,
                'status' => $profile->onboarding_status,
                'expected' => implode(', ', $allowedForWebhook),
            ]);

            return response()->json(['error' => 'Profile not in approvable state'], 400);
        }

        try {
            // Step 4: Verify payment with Paystack API and validate amount
            $verifiedData = $paystackService->verifyPayment($reference);

            $billingCycle = $profile->preferred_billing_cycle ?? 'monthly';
            $expectedAmount = $paystackService->getAmountForBillingCycle($billingCycle) * 100;
            $paidAmount = (int) ($verifiedData['amount'] ?? 0);

            Log::info('PaystackWebhook: amount check', [
                'billing_cycle' => $billingCycle,
                'expected' => $expectedAmount,
                'paid' => $paidAmount,
            ]);

            if ($paidAmount < $expectedAmount) {
                Log::warning('PaystackWebhook: payment amount mismatch', [
                    'reference' => $reference,
                    'billing_cycle' => $billingCycle,
                    'expected' => $expectedAmount,
                    'paid' => $paidAmount,
                ]);

                AuditLogService::log(
                    action: 'paystack_amount_mismatch',
                    model: $profile,
                    old: ['onboarding_status' => $profile->onboarding_status],
                    new: ['onboarding_status' => $profile->onboarding_status, 'reason' => "Paid {$paidAmount} instead of {$expectedAmount}"],
                    targetUserId: $profile->user_id,
                );

                return response()->json(['error' => 'Payment amount does not match billing cycle'], 400);
            }

            // Step 5: Record payment via shared service
            app(MembershipStateService::class)->recordPayment($profile, $profile->user, $billingCycle);

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
}
