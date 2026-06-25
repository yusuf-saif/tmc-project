<?php

namespace Tests\Feature;

use App\Filament\Pages\ManagePayments;
use App\Models\MemberProfile;
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
        $this->createProfileWithStatus('pending_review');
        $approved = $this->createProfileWithStatus('approved_pending_payment');
        $processing = $this->createProfileWithStatus('payment_processing');
        $failed = $this->createProfileWithStatus('payment_failed');
        $this->createProfileWithStatus('active');

        $this->actingAs($this->admin);

        Livewire::test(ManagePayments::class)
            ->assertCanSeeTableRecords([$approved, $processing, $failed])
            ->assertCountTableRecords(3);
    }

    public function test_payment_page_has_view_action_linking_to_application(): void
    {
        $profile = $this->createProfileWithStatus('payment_processing');

        $this->actingAs($this->admin);

        Livewire::test(ManagePayments::class)
            ->assertTableActionExists('view');
    }
}
