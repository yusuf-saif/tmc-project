<?php

namespace Tests\Feature;

use App\Filament\Pages\ManagePayments;
use App\Filament\Resources\PaymentRecordResource\Pages\ListPaymentRecords;
use App\Models\MemberProfile;
use App\Models\PaymentRecord;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPaymentApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    protected function createProfileWithStatus(string $status): MemberProfile
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        return MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => $status,
            'membership_id' => 'TMC-M-1447-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'payment_submitted_at' => now(),
            'preferred_billing_cycle' => 'monthly',
        ]);
    }

    public function test_admin_can_view_payment_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/manage-payments')
            ->assertOk();
    }

    public function test_non_admin_cannot_view_payment_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get('/admin/manage-payments')
            ->assertForbidden();
    }

    public function test_payment_page_shows_payment_statuses(): void
    {
        $onboarding = $this->createProfileWithStatus('onboarding');
        $active = $this->createProfileWithStatus('active');

        $this->actingAs($this->admin);

        Livewire::test(ManagePayments::class)
            ->assertCanSeeTableRecords([$onboarding])
            ->assertCountTableRecords(1);
    }

    public function test_payment_page_has_view_action_linking_to_application(): void
    {
        $this->createProfileWithStatus('onboarding');

        $this->actingAs($this->admin);

        Livewire::test(ManagePayments::class)
            ->assertTableActionExists('view');
    }

    public function test_double_verify_through_admin_page_extends_period_once(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'onboarding',
            'membership_id' => 'TMC-M-1447-100',
            'payment_source' => 'bank_transfer',
            'payment_status' => 'pending_verification',
            'payment_submitted_at' => now(),
            'preferred_billing_cycle' => 'monthly',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ManagePayments::class)
            ->callTableAction('verify', $profile)
            ->callTableAction('verify', $profile);

        $profile->refresh();
        $this->assertEquals('member', $profile->onboarding_status);
        $this->assertNotNull($profile->current_period_ends_at);
        $this->assertSame(1, PaymentRecord::query()->where('member_profile_id', $profile->id)->count());
        $this->assertSame(1, PaymentRecord::query()->where('member_profile_id', $profile->id)->where('status', 'paid')->count());
    }

    public function test_admin_can_view_payment_records_resource(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/payment-records')
            ->assertOk();
    }

    public function test_non_admin_cannot_view_payment_records_resource(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get('/admin/payment-records')
            ->assertForbidden();
    }

    public function test_cancel_action_marks_pending_record_cancelled(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'onboarding',
            'membership_id' => 'TMC-M-1447-101',
        ]);

        $record = PaymentRecord::create([
            'user_id' => $user->id,
            'member_profile_id' => $profile->id,
            'provider' => 'manual',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ListPaymentRecords::class)
            ->callTableAction('cancel', $record);

        $this->assertEquals('cancelled', $record->fresh()->status);
    }

    public function test_cancel_action_hidden_for_paid_records(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'member',
            'membership_id' => 'TMC-M-1447-102',
        ]);

        PaymentRecord::create([
            'user_id' => $user->id,
            'member_profile_id' => $profile->id,
            'provider' => 'manual',
            'status' => 'paid',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ListPaymentRecords::class)
            ->assertTableActionHidden('cancel');
    }
}
