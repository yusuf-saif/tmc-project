<?php

namespace Tests\Feature;

use App\Filament\Pages\SettingsPage;
use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Setting;
use App\Models\User;
use App\Services\CoinsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = $this->createOnboardedUser('admin@example.com', 'ADMIN700', 'admin');
        $this->superAdmin = $this->createOnboardedUser('super@example.com', 'SUPER700', 'super_admin');
    }

    public function test_admin_dashboard_loads(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_stats_widget_counts_are_correct(): void
    {
        $this->createOnboardedUser('member1@example.com', 'MEM70001', 'member');
        $this->createOnboardedUser('member2@example.com', 'MEM70002', 'member');
        $this->createOnboardedUser('volunteer@example.com', 'VOL70003', 'volunteer');

        $this->actingAs($this->admin);

        $stats = StatsOverviewWidget::statsData();

        $this->assertGreaterThanOrEqual(3, $stats['total_members']);
    }

    public function test_admin_can_view_user_list(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_admin_can_suspend_member(): void
    {
        $member = $this->createOnboardedUser('suspend@example.com', 'SUSP700', 'member');

        $this->actingAs($this->admin);

        UserResource::suspend($member, 'Repeated community guideline breaches.');

        $member->refresh();

        $this->assertSame('suspended', $member->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user_suspended',
            'auditable_id' => $member->id,
        ]);
    }

    public function test_suspended_member_cannot_login(): void
    {
        $member = User::factory()->create([
            'email' => 'blocked@example.com',
            'password' => 'Password123!',
            'status' => 'suspended',
            'referral_code' => 'BLCK7001',
            'email_verified_at' => now(),
        ]);
        $member->assignRole('member');
        $member->profile()->create(['display_name' => $member->name, 'onboarding_completed_at' => now()]);

        $response = $this->post('/login', [
            'email' => 'blocked@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(302);
        $this->assertGuest();
        $this->assertNotEquals(route('home'), $response->headers->get('Location'));
    }

    public function test_super_admin_can_change_role(): void
    {
        $member = $this->createOnboardedUser('changerole@example.com', 'ROLE7001', 'member');

        $this->actingAs($this->superAdmin);

        UserResource::changeRole($member, 'volunteer', 'Trusted service');

        $member->refresh();

        $this->assertTrue($member->hasRole('volunteer'));
        $this->assertDatabaseHas('user_role_history', [
            'user_id' => $member->id,
            'new_role' => 'volunteer',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'role_changed',
            'auditable_id' => $member->id,
        ]);
    }

    public function test_non_super_admin_cannot_change_role(): void
    {
        $this->assertFalse(UserResource::canChangeRole($this->admin));
    }

    public function test_audit_log_is_read_only(): void
    {
        $this->actingAs($this->superAdmin);

        $this->assertFalse(AuditLogResource::canCreate());
        $this->assertFalse(AuditLogResource::canEdit(null));
        $this->assertFalse(AuditLogResource::canDelete(null));
    }

    public function test_settings_page_saves_values(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SettingsPage::class)
            ->set('bankDetails', 'Bank XYZ\n12345678')
            ->call('save');

        $this->assertSame('Bank XYZ\n12345678', Setting::getValue('bank_details'));
    }

    public function test_admin_cannot_access_settings(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_coins_awarded_from_admin(): void
    {
        $member = $this->createOnboardedUser('coins@example.com', 'COIN7001', 'member');

        $this->actingAs($this->admin);

        UserResource::awardCoins($member, 100, 'Manual reward');

        $this->assertSame(100, CoinsService::getBalance($member));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'coins_awarded',
            'auditable_id' => $member->id,
        ]);
    }

    protected function createOnboardedUser(string $email, string $referralCode, string $role): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'password' => 'Password123!',
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => $referralCode,
        ]);

        $user->assignRole($role);
        $user->profile()->create([
            'display_name' => $user->name,
            'onboarding_completed_at' => now(),
        ]);

        return $user;
    }
}
