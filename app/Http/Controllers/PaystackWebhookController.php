<?php

namespace App\Http\Controllers;

use App\Events\MembershipActivated;
use App\Models\MemberProfile;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackService $paystackService): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->header('x-paystack-signature');
        $webhookSecret = config('paystack.webhookSecret');

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

        $event = $payload['event'] ?? '';

        if ($event !== 'charge.success') {
            return response()->json(['status' => 'ignored']);
        }

        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? null;
        $metadata = $data['metadata'] ?? [];
        $userId = $metadata['user_id'] ?? null;
        $billingCycle = $metadata['billing_cycle'] ?? 'monthly';

        if (! $reference) {
            Log::warning('PaystackWebhook: no reference in payload', ['payload' => $data]);

            return response()->json(['error' => 'Missing reference'], 400);
        }

        if (! $userId) {
            $email = $data['customer']['email'] ?? $data['email'] ?? null;
            if ($email) {
                $userId = User::where('email', $email)->value('id');
            }
        }

        if (! $userId) {
            Log::warning('PaystackWebhook: could not resolve user', ['payload' => $data]);

            return response()->json(['status' => 'no_user']);
        }

        $user = User::find($userId);
        if (! $user) {
            return response()->json(['status' => 'user_not_found']);
        }

        $profile = MemberProfile::where('user_id', $user->id)->first();
        if (! $profile) {
            Log::warning('PaystackWebhook: no member profile', ['user_id' => $userId]);

            return response()->json(['status' => 'no_profile']);
        }

        if ($profile->payment_verified_at !== null) {
            return response()->json(['status' => 'already_verified']);
        }

        try {
            $verifiedData = $paystackService->verifyPayment($reference);

            $expectedAmount = $paystackService->getAmountForBillingCycle($billingCycle) * 100;
            $paidAmount = (int) ($verifiedData['amount'] ?? 0);

            if ($paidAmount < $expectedAmount) {
                Log::warning('PaystackWebhook: payment amount mismatch', [
                    'reference' => $reference,
                    'billing_cycle' => $billingCycle,
                    'expected' => $expectedAmount,
                    'paid' => $paidAmount,
                ]);

                $profile->forceFill(['onboarding_status' => 'payment_failed'])->saveQuietly();

                return response()->json(['error' => 'Payment amount does not match billing cycle'], 400);
            }

            $nextDue = match ($billingCycle) {
                'quarterly' => now()->addMonths(3),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            };

            DB::transaction(function () use ($profile, $user, $reference, $nextDue): void {
                $locked = MemberProfile::where('id', $profile->id)->lockForUpdate()->first();

                if ($locked->payment_verified_at !== null) {
                    return;
                }

                $locked->update([
                    'onboarding_status' => 'active',
                    'paystack_reference' => $reference,
                    'payment_verified_at' => now(),
                    'activated_at' => now(),
                    'next_due_at' => $nextDue,
                ]);

                $user->forceFill(['status' => 'active'])->saveQuietly();

                $legacy = $user->profile;
                if ($legacy) {
                    $legacy->forceFill([
                        'membership_status' => 'active',
                        'payment_status' => 'paid',
                        'membership_fee_paid_at' => now(),
                    ])->saveQuietly();
                }
            });

            $profile->refresh();

            MembershipActivated::dispatch($user, $profile->membership_id ?? 'N/A', $user);

            Log::info('PaystackWebhook: membership activated', [
                'user_id' => $user->id,
                'reference' => $reference,
                'billing_cycle' => $billingCycle,
                'next_due_at' => $nextDue->toIso8601String(),
            ]);

            return response()->json(['status' => 'activated']);
        } catch (\Throwable $e) {
            Log::error('PaystackWebhook: activation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            try {
                $profile->forceFill(['onboarding_status' => 'payment_failed'])->saveQuietly();
            } catch (\Throwable $inner) {
                Log::warning('PaystackWebhook: could not set payment_failed', ['error' => $inner->getMessage()]);
            }

            return response()->json(['error' => 'Activation failed'], 500);
        }
    }
}
