<?php

namespace Tests\Feature;

use App\Filament\Pages\ManagePayments;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeratorAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class]);
    }

    protected function createUserWithRole(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole($role);

        return $user->fresh();
    }

    public function test_moderator_cannot_manage_users(): void
    {
        $moderator = $this->createUserWithRole('moderator');
        $this->assertFalse(UserResource::canManageUsers($moderator));
    }

    public function test_admin_can_manage_users(): void
    {
        $admin = $this->createUserWithRole('admin');
        $this->assertTrue(UserResource::canManageUsers($admin));
    }

    public function test_super_admin_can_manage_users(): void
    {
        $superAdmin = $this->createUserWithRole('super_admin');
        $this->assertTrue(UserResource::canManageUsers($superAdmin));
    }

    public function test_moderator_cannot_verify_payments(): void
    {
        $moderator = $this->createUserWithRole('moderator');
        $this->assertFalse(UserResource::canVerifyPayments($moderator));
    }

    public function test_admin_can_verify_payments(): void
    {
        $admin = $this->createUserWithRole('admin');
        $this->assertTrue(UserResource::canVerifyPayments($admin));
    }

    public function test_super_admin_can_verify_payments(): void
    {
        $superAdmin = $this->createUserWithRole('super_admin');
        $this->assertTrue(UserResource::canVerifyPayments($superAdmin));
    }

    public function test_moderator_cannot_access_manage_payments_page(): void
    {
        $moderator = $this->createUserWithRole('moderator');
        $this->actingAs($moderator);
        $this->assertFalse(ManagePayments::canAccess());
    }

    public function test_admin_can_access_manage_payments_page(): void
    {
        $admin = $this->createUserWithRole('admin');
        $this->actingAs($admin);
        $this->assertTrue(ManagePayments::canAccess());
    }

    public function test_super_admin_can_access_manage_payments_page(): void
    {
        $superAdmin = $this->createUserWithRole('super_admin');
        $this->actingAs($superAdmin);
        $this->assertTrue(ManagePayments::canAccess());
    }

    public function test_moderator_cannot_change_roles(): void
    {
        $moderator = $this->createUserWithRole('moderator');
        $this->assertFalse(UserResource::canChangeRole($moderator));
    }

    public function test_admin_cannot_change_roles(): void
    {
        $admin = $this->createUserWithRole('admin');
        $this->assertFalse(UserResource::canChangeRole($admin));
    }

    public function test_super_admin_can_change_roles(): void
    {
        $superAdmin = $this->createUserWithRole('super_admin');
        $this->assertTrue(UserResource::canChangeRole($superAdmin));
    }
}
