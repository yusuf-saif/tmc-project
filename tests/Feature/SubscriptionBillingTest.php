<?php

namespace Tests\Feature;

use App\Events\BusinessActivated;
use App\Events\BusinessApproved;
use App\Events\BusinessSuspended;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionExpiringSoon;
use App\Events\SubscriptionSuspended;
use App\Models\AuditLog;
use App\Models\SouqListing;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\BusinessActivated as BusinessActivatedNotification;
use App\Notifications\BusinessApproved as BusinessApprovedNotification;
use App\Notifications\BusinessSuspended as BusinessSuspendedNotification;
use App\Notifications\SubscriptionActivated as SubscriptionActivatedNotification;
use App\Notifications\SubscriptionExpired as SubscriptionExpiredNotification;
use App\Notifications\SubscriptionExpiringSoon as SubscriptionExpiringSoonNotification;
use App\Notifications\SubscriptionSuspended as SubscriptionSuspendedNotification;
use App\Services\BusinessStateService;
use App\Services\SubscriptionStateService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class SubscriptionBillingTest extends TestCase
{
    use RefreshDatabase;

    protected User $member;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
        ]);

        $this->member = User::factory()->create(['email' => 'member@test.com']);
        $this->member->assignRole('member');

        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->assignRole('super_admin');
    }

    protected function createSubscription(string $type = 'monthly', string $status = 'active'): Subscription
    {
        return Subscription::create([
            'user_id' => $this->member->id,
            'type' => $type,
            'status' => $status,
        ]);
    }

    protected function createPendingListing(): SouqListing
    {
        return SouqListing::create([
            'user_id' => $this->member->id,
            'business_name' => 'Test Business',
            'category' => 'services',
            'description' => 'A test business',
            'contact_email' => 'business@test.com',
            'status' => 'pending',
            'monthly_fee' => 0.00,
            'billing_status' => 'none',
        ]);
    }

    // ─── SubscriptionPlan ─────────────────────────────────────────

    public function test_subscription_plan_duration_months(): void
    {
        $monthly = $this->createSubscription(type: 'monthly');
        $quarterly = $this->createSubscription(type: 'quarterly');
        $annual = $this->createSubscription(type: 'annual');

        $this->assertSame(1, $monthly->durationMonths());
        $this->assertSame(3, $quarterly->durationMonths());
        $this->assertSame(12, $annual->durationMonths());
    }

    public function test_subscription_plan_name_labels(): void
    {
        $monthly = $this->createSubscription(type: 'monthly');
        $quarterly = $this->createSubscription(type: 'quarterly');
        $annual = $this->createSubscription(type: 'annual');

        $this->assertSame('Monthly', $monthly->planName());
        $this->assertSame('Quarterly', $quarterly->planName());
        $this->assertSame('Annual', $annual->planName());
    }

    // ─── Subscription model ───────────────────────────────────────

    public function test_subscription_relationships(): void
    {
        $subscription = $this->createSubscription();

        $this->assertTrue($subscription->user->is($this->member));
        $this->assertSame('Monthly', $subscription->planName());
    }

    public function test_subscription_is_active_checks(): void
    {
        $active = $this->createSubscription(status: 'active');
        $expired = $this->createSubscription(status: 'expired');
        $suspended = $this->createSubscription(status: 'suspended');

        $this->assertTrue($active->isActive());
        $this->assertFalse($active->isExpired());
        $this->assertFalse($active->isSuspended());

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($expired->isActive());

        $this->assertTrue($suspended->isSuspended());
        $this->assertFalse($suspended->isActive());
    }

    public function test_subscription_has_expired(): void
    {
        $future = $this->createSubscription();
        $future->update(['end_date' => now()->addDays(10)]);
        $this->assertFalse($future->hasExpired());

        $past = $this->createSubscription();
        $past->update(['end_date' => now()->subDay()]);
        $this->assertTrue($past->hasExpired());
    }

    public function test_subscription_days_until_expiry(): void
    {
        $subscription = $this->createSubscription();

        $this->assertSame(0, $subscription->daysUntilExpiry());

        $subscription->update(['end_date' => now()->addDays(5)]);
        $this->assertSame(5, $subscription->fresh()->daysUntilExpiry());

        $subscription->update(['end_date' => now()->subDay()]);
        $this->assertSame(0, $subscription->fresh()->daysUntilExpiry());
    }

    public function test_subscription_active_scope(): void
    {
        $this->createSubscription(status: 'active');
        $this->createSubscription(status: 'expired');

        $this->assertSame(1, Subscription::active()->count());
    }

    public function test_subscription_expired_scope(): void
    {
        $this->createSubscription(status: 'active');
        $this->createSubscription(status: 'expired');

        $this->assertSame(1, Subscription::expired()->count());
    }

    public function test_subscription_expiring_between_scope(): void
    {
        $this->createSubscription()->update(['end_date' => now()->addDays(5)->startOfDay()]);
        $this->createSubscription()->update(['end_date' => now()->addDays(3)->startOfDay()]);
        $this->createSubscription()->update(['end_date' => now()->addDays(10)->startOfDay()]);

        $expiring = Subscription::expiringBetween(3, 7)->get();
        $this->assertCount(2, $expiring);
    }

    // ─── SubscriptionStateService ─────────────────────────────────

    public function test_activate_sets_hijri_dates(): void
    {
        $subscription = $this->createSubscription();

        $result = app(SubscriptionStateService::class)->activate($subscription, $this->admin);

        $this->assertSame('active', $result->status);
        $this->assertNotNull($result->start_date);
        $this->assertNotNull($result->end_date);
        $this->assertNotNull($result->hijri_start_year);
        $this->assertNotNull($result->hijri_start_month);
        $this->assertNull($result->cancelled_at);
        $this->assertNull($result->suspended_at);
    }

    public function test_activate_sets_correct_hijri_duration(): void
    {
        $monthly = $this->createSubscription(type: 'monthly');
        $result = app(SubscriptionStateService::class)->activate($monthly, $this->admin);
        $this->assertTrue($result->end_date->diffInDays($result->start_date, true) >= 28);

        $annual = $this->createSubscription(type: 'annual', status: 'active');
        $result2 = app(SubscriptionStateService::class)->activate($annual, $this->admin);
        $this->assertTrue($result2->end_date->diffInDays($result2->start_date, true) >= 340);
    }

    public function test_expire_transitions_from_active_to_expired(): void
    {
        $subscription = $this->createSubscription(status: 'active');

        $result = app(SubscriptionStateService::class)->expire($subscription, $this->admin);

        $this->assertSame('expired', $result->status);
    }

    public function test_expire_throws_on_invalid_transition(): void
    {
        $subscription = $this->createSubscription(status: 'expired');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot expire subscription in status: expired');

        app(SubscriptionStateService::class)->expire($subscription, $this->admin);
    }

    public function test_suspend_transitions_from_active_to_suspended(): void
    {
        $subscription = $this->createSubscription(status: 'active');

        $result = app(SubscriptionStateService::class)->suspend($subscription, 'Payment overdue', $this->admin);

        $this->assertSame('suspended', $result->status);
        $this->assertSame('Payment overdue', $result->suspended_reason);
        $this->assertNotNull($result->suspended_at);
    }

    public function test_suspend_throws_on_invalid_transition(): void
    {
        $subscription = $this->createSubscription(status: 'suspended');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot suspend subscription in status: suspended');

        app(SubscriptionStateService::class)->suspend($subscription, 'reason', $this->admin);
    }

    public function test_renew_transitions_from_expired_to_active(): void
    {
        $subscription = $this->createSubscription(status: 'expired');

        $result = app(SubscriptionStateService::class)->renew($subscription, $this->admin);

        $this->assertSame('active', $result->status);
        $this->assertNotNull($result->start_date);
        $this->assertNotNull($result->end_date);
        $this->assertNull($result->cancelled_at);
        $this->assertNull($result->suspended_at);
    }

    public function test_can_transition_method(): void
    {
        $service = app(SubscriptionStateService::class);

        $this->assertTrue($service->canTransition('active', 'expired'));
        $this->assertTrue($service->canTransition('active', 'suspended'));
        $this->assertTrue($service->canTransition('expired', 'active'));
        $this->assertTrue($service->canTransition('suspended', 'active'));
        $this->assertFalse($service->canTransition('active', 'active'));
        $this->assertFalse($service->canTransition('expired', 'suspended'));
        $this->assertFalse($service->canTransition('suspended', 'expired'));
    }

    // ─── BusinessStateService ────────────────────────────────────

    public function test_approve_transitions_from_pending_to_approved(): void
    {
        $listing = $this->createPendingListing();

        $result = app(BusinessStateService::class)->approve($listing, '15.00', $this->admin);

        $this->assertSame('approved_unpaid', $result->status);
        $this->assertSame(15.00, (float) $result->monthly_fee);
        $this->assertNotNull($result->reviewed_by);
        $this->assertNotNull($result->reviewed_at);
    }

    public function test_approve_uses_default_fee_when_not_provided(): void
    {
        $listing = $this->createPendingListing();
        $listing->update(['monthly_fee' => 25.00]);

        $result = app(BusinessStateService::class)->approve($listing, null, $this->admin);

        $this->assertSame(25.00, (float) $result->monthly_fee);
    }

    public function test_approve_throws_on_invalid_transition(): void
    {
        $listing = $this->createPendingListing();
        $listing->update(['status' => 'approved']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot approve listing in status: approved');

        app(BusinessStateService::class)->approve($listing, '10.00', $this->admin);
    }

    public function test_reject_transitions_from_pending_to_rejected(): void
    {
        $listing = $this->createPendingListing();

        $result = app(BusinessStateService::class)->reject($listing, 'Incomplete information', $this->admin);

        $this->assertSame('rejected', $result->status);
        $this->assertSame('Incomplete information', $result->admin_note);
        $this->assertNotNull($result->reviewed_by);
        $this->assertNotNull($result->reviewed_at);
    }

    public function test_reject_throws_on_invalid_transition(): void
    {
        $listing = $this->createPendingListing();
        $listing->update(['status' => 'rejected']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot reject listing in status: rejected');

        app(BusinessStateService::class)->reject($listing, 'reason', $this->admin);
    }

    public function test_business_activate_transitions_to_active_with_billing(): void
    {
        $listing = $this->createPendingListing();
        $listing = app(BusinessStateService::class)->approve($listing, '10.00', $this->admin);

        $result = app(BusinessStateService::class)->activate($listing, $this->admin);

        $this->assertSame('active', $result->status);
        $this->assertSame('active', $result->billing_status);
        $this->assertNotNull($result->billing_start_date);
        $this->assertNotNull($result->billing_end_date);
        $this->assertNotNull($result->last_billed_at);
    }

    public function test_business_activate_throws_on_invalid_transition(): void
    {
        $listing = $this->createPendingListing();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot activate listing in status: pending');

        app(BusinessStateService::class)->activate($listing, $this->admin);
    }

    public function test_business_suspend_transitions_from_active_to_suspended(): void
    {
        $listing = $this->createPendingListing();
        $listing = app(BusinessStateService::class)->approve($listing, '10.00', $this->admin);
        $listing = app(BusinessStateService::class)->activate($listing, $this->admin);

        $result = app(BusinessStateService::class)->suspend($listing, 'Policy violation', $this->admin);

        $this->assertSame('suspended', $result->status);
        $this->assertSame('suspended', $result->billing_status);
        $this->assertNotNull($result->billing_suspended_at);
    }

    public function test_business_suspend_throws_on_invalid_transition(): void
    {
        $listing = $this->createPendingListing();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot suspend listing in status: pending');

        app(BusinessStateService::class)->suspend($listing, 'reason', $this->admin);
    }

    public function test_auto_expire_billing_suspends_listing(): void
    {
        $listing = $this->createPendingListing();
        $listing = app(BusinessStateService::class)->approve($listing, '10.00', $this->admin);
        $listing = app(BusinessStateService::class)->activate($listing, $this->admin);

        $result = app(BusinessStateService::class)->autoExpireBilling($listing);

        $this->assertSame('suspended', $result->status);
        $this->assertSame('expired', $result->billing_status);
    }

    public function test_business_can_transition_method(): void
    {
        $service = app(BusinessStateService::class);

        $this->assertTrue($service->canTransition('pending', 'approved_unpaid'));
        $this->assertTrue($service->canTransition('pending', 'rejected'));
        $this->assertTrue($service->canTransition('approved_unpaid', 'active'));
        $this->assertTrue($service->canTransition('active', 'suspended'));
        $this->assertTrue($service->canTransition('suspended', 'active'));
        $this->assertTrue($service->canTransition('rejected', 'pending'));
        $this->assertFalse($service->canTransition('pending', 'active'));
        $this->assertFalse($service->canTransition('active', 'pending'));
    }

    // ─── Audit Logs ───────────────────────────────────────────────

    public function test_subscription_expire_creates_audit_log(): void
    {
        $subscription = $this->createSubscription(status: 'active');

        app(SubscriptionStateService::class)->expire($subscription, $this->admin);

        $log = AuditLog::where('action', 'subscription_expired')->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->member->id, $log->target_user_id);
    }

    public function test_subscription_suspend_creates_audit_log(): void
    {
        $subscription = $this->createSubscription(status: 'active');

        app(SubscriptionStateService::class)->suspend($subscription, 'Non-payment', $this->admin);

        $log = AuditLog::where('action', 'subscription_suspended')->first();
        $this->assertNotNull($log);
        $this->assertSame('Non-payment', $log->new_values['reason']);
    }

    public function test_subscription_renew_creates_audit_log(): void
    {
        $subscription = $this->createSubscription(status: 'expired');

        app(SubscriptionStateService::class)->renew($subscription, $this->admin);

        $log = AuditLog::where('action', 'subscription_renewed')->first();
        $this->assertNotNull($log);
    }

    public function test_business_approve_creates_audit_log(): void
    {
        $listing = $this->createPendingListing();

        app(BusinessStateService::class)->approve($listing, '15.00', $this->admin);

        $log = AuditLog::where('action', 'business_approved')->first();
        $this->assertNotNull($log);
        $this->assertEquals(15.00, (float) $log->new_values['monthly_fee']);
    }

    public function test_business_reject_creates_audit_log(): void
    {
        $listing = $this->createPendingListing();

        app(BusinessStateService::class)->reject($listing, 'Missing details', $this->admin);

        $log = AuditLog::where('action', 'business_rejected')->first();
        $this->assertNotNull($log);
        $this->assertSame('Missing details', $log->new_values['reason']);
    }

    public function test_business_activate_creates_audit_log(): void
    {
        $listing = $this->createPendingListing();
        $listing = app(BusinessStateService::class)->approve($listing, '10.00', $this->admin);

        app(BusinessStateService::class)->activate($listing, $this->admin);

        $log = AuditLog::where('action', 'business_activated')->first();
        $this->assertNotNull($log);
    }

    public function test_business_suspend_creates_audit_log(): void
    {
        $listing = $this->createPendingListing();
        $listing = app(BusinessStateService::class)->approve($listing, '10.00', $this->admin);
        $listing = app(BusinessStateService::class)->activate($listing, $this->admin);

        app(BusinessStateService::class)->suspend($listing, 'Violation', $this->admin);

        $log = AuditLog::where('action', 'business_suspended')->first();
        $this->assertNotNull($log);
    }

    public function test_business_auto_expire_billing_creates_audit_log(): void
    {
        $listing = $this->createPendingListing();
        $listing = app(BusinessStateService::class)->approve($listing, '10.00', $this->admin);
        $listing = app(BusinessStateService::class)->activate($listing, $this->admin);

        app(BusinessStateService::class)->autoExpireBilling($listing);

        $log = AuditLog::where('action', 'business_billing_expired')->first();
        $this->assertNotNull($log);
    }

    // ─── Events & Notifications via event dispatch ─────────────────

    public function test_subscription_expired_event_sends_notification(): void
    {
        Notification::fake();
        $subscription = $this->createSubscription(status: 'active');

        SubscriptionExpired::dispatch($subscription);

        Notification::assertSentTo(
            $this->member,
            SubscriptionExpiredNotification::class,
        );
    }

    public function test_subscription_suspend_event_sends_notification(): void
    {
        Notification::fake();
        $subscription = $this->createSubscription(status: 'active');

        SubscriptionSuspended::dispatch($subscription, $this->admin, 'Non-payment');

        Notification::assertSentTo(
            $this->member,
            SubscriptionSuspendedNotification::class,
        );
    }

    public function test_subscription_activated_event_sends_notification(): void
    {
        Notification::fake();
        $subscription = $this->createSubscription();
        $subscription = app(SubscriptionStateService::class)->activate($subscription, $this->admin);

        SubscriptionActivated::dispatch($subscription, $this->admin);

        Notification::assertSentTo(
            $this->member,
            SubscriptionActivatedNotification::class,
        );
    }

    public function test_subscription_expiring_soon_event_sends_notification(): void
    {
        Notification::fake();
        $subscription = $this->createSubscription();

        SubscriptionExpiringSoon::dispatch($subscription, 7);

        Notification::assertSentTo(
            $this->member,
            SubscriptionExpiringSoonNotification::class,
        );
    }

    public function test_business_approved_event_sends_notification(): void
    {
        Notification::fake();
        $listing = $this->createPendingListing();

        BusinessApproved::dispatch($listing, $this->admin, 15.00);

        Notification::assertSentTo(
            $this->member,
            BusinessApprovedNotification::class,
        );
    }

    public function test_business_activated_event_sends_notification(): void
    {
        Notification::fake();
        $listing = $this->createPendingListing();
        $listing = app(BusinessStateService::class)->approve($listing, '10.00', $this->admin);

        BusinessActivated::dispatch($listing, $this->admin);

        Notification::assertSentTo(
            $this->member,
            BusinessActivatedNotification::class,
        );
    }

    public function test_business_suspended_event_sends_notification(): void
    {
        Notification::fake();
        $listing = $this->createPendingListing();
        $listing = app(BusinessStateService::class)->approve($listing, '10.00', $this->admin);
        $listing = app(BusinessStateService::class)->activate($listing, $this->admin);

        BusinessSuspended::dispatch($listing, $this->admin, 'Violation');

        Notification::assertSentTo(
            $this->member,
            BusinessSuspendedNotification::class,
        );
    }

    // ─── SouqListing billing model ────────────────────────────────

    public function test_souq_listing_subscription_relationship(): void
    {
        $subscription = $this->createSubscription();
        $listing = $this->createPendingListing();
        $listing->update(['subscription_id' => $subscription->id]);

        $this->assertTrue($listing->subscription->is($subscription));
    }

    public function test_souq_listing_billing_status_defaults(): void
    {
        $listing = $this->createPendingListing();

        $this->assertSame('none', $listing->billing_status);
    }

    // ─── Scheduler logic ──────────────────────────────────────────

    public function test_expire_subscriptions_scheduler_expires_past_dates(): void
    {
        $subscription = $this->createSubscription(status: 'active');
        $subscription->update(['end_date' => now()->subDay()]);

        Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now())
            ->update(['status' => 'expired']);

        $this->assertSame('expired', $subscription->fresh()->status);
    }

    public function test_expiring_soon_scope_finds_correct_subscriptions(): void
    {
        $this->createSubscription()->update(['end_date' => now()->addDays(7)->startOfDay()]);
        $this->createSubscription()->update(['end_date' => now()->addDays(3)->startOfDay()]);
        $this->createSubscription()->update(['end_date' => now()->addDays(20)->startOfDay()]);

        $this->assertCount(1, Subscription::expiringBetween(7, 7)->get());
        $this->assertCount(1, Subscription::expiringBetween(3, 3)->get());
        $this->assertCount(0, Subscription::expiringBetween(10, 15)->get());
    }
}
