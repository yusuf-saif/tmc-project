<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->member = User::factory()->create();
        $this->member->assignRole('member');
    }

    protected function createProfileWithReceipt(?string $path = null): MemberProfile
    {
        return MemberProfile::create([
            'user_id' => $this->member->id,
            'onboarding_status' => 'onboarding',
            'payment_proof_path' => $path,
            'preferred_billing_cycle' => 'monthly',
        ]);
    }

    public function test_admin_can_download_receipt(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('receipt.jpg', 100, 100);
        $path = $file->store('payment-proofs', 'public');

        $profile = $this->createProfileWithReceipt($path);

        $this->actingAs($this->admin)
            ->get(route('admin.receipt.download', $profile))
            ->assertOk();
    }

    public function test_member_cannot_download_receipt(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('receipt.jpg', 100, 100);
        $path = $file->store('payment-proofs', 'public');

        $profile = $this->createProfileWithReceipt($path);

        $this->actingAs($this->member)
            ->get(route('admin.receipt.download', $profile))
            ->assertStatus(403);
    }

    public function test_receipt_download_returns_404_when_no_receipt(): void
    {
        $profile = $this->createProfileWithReceipt(null);

        $this->actingAs($this->admin)
            ->get(route('admin.receipt.download', $profile))
            ->assertStatus(404);
    }

    public function test_receipt_download_returns_404_when_file_missing(): void
    {
        $profile = $this->createProfileWithReceipt('payment-proofs/missing.jpg');

        Storage::shouldReceive('disk->exists')->andReturn(false);

        $this->actingAs($this->admin)
            ->get(route('admin.receipt.download', $profile))
            ->assertStatus(404);
    }
}
