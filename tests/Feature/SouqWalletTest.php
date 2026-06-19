<?php

namespace Tests\Feature;

use App\Filament\Resources\SouqListingResource;
use App\Livewire\Souq\ApplyForm;
use App\Livewire\Wallet\WalletScreen;
use App\Models\SouqListing;
use App\Models\User;
use App\Services\CoinsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SouqWalletTest extends TestCase
{
    use RefreshDatabase;

    protected User $member;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RoleSeeder::class);

        $this->member = $this->createOnboardedUser('member@example.com', 'MEMB5001', 'member');
        $this->admin = $this->createOnboardedUser('admin@example.com', 'ADMIN500', 'admin');
    }

    public function test_member_can_view_souq_directory(): void
    {
        $this->actingAs($this->member)
            ->get('/souq')
            ->assertOk();
    }

    public function test_member_can_filter_and_search_souq_directory(): void
    {
        SouqListing::query()->create([
            'user_id' => $this->member->id,
            'business_name' => 'Noor Threads',
            'category' => 'fashion',
            'description' => 'Fashion listing',
            'contact_email' => 'noor@example.com',
            'status' => 'approved',
        ]);

        SouqListing::query()->create([
            'user_id' => $this->member->id,
            'business_name' => 'Barakah Bakes',
            'category' => 'food_catering',
            'description' => 'Food listing',
            'contact_email' => 'barakah@example.com',
            'status' => 'approved',
        ]);

        $this->actingAs($this->member)
            ->get('/souq?category=fashion&search=Noor')
            ->assertOk()
            ->assertSee('Noor Threads')
            ->assertDontSee('Barakah Bakes');
    }

    public function test_only_approved_listings_visible(): void
    {
        $pending = SouqListing::query()->create([
            'user_id' => $this->member->id,
            'business_name' => 'Hidden Listing',
            'category' => 'services',
            'description' => 'Pending listing',
            'contact_email' => 'hidden@example.com',
            'status' => 'pending',
        ]);

        $this->actingAs($this->member)
            ->get('/souq')
            ->assertDontSee('Hidden Listing');

        $pending->update(['status' => 'approved']);

        $this->actingAs($this->member)
            ->get('/souq')
            ->assertSee('Hidden Listing');
    }

    public function test_member_can_view_listing_detail(): void
    {
        $listing = SouqListing::query()->create([
            'user_id' => $this->member->id,
            'business_name' => 'Visible Listing',
            'category' => 'creative',
            'description' => 'Approved listing',
            'contact_email' => 'visible@example.com',
            'status' => 'approved',
        ]);

        $this->actingAs($this->member)
            ->get('/souq/'.$listing->slug)
            ->assertOk()
            ->assertSee('Visible Listing');
    }

    public function test_member_can_submit_souq_application(): void
    {
        Livewire::actingAs($this->member)
            ->test(ApplyForm::class)
            ->set('businessName', 'New Business')
            ->set('category', 'fashion')
            ->set('description', 'A beautiful modestwear project for the sisterhood.')
            ->set('contactEmail', 'newbusiness@example.com')
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('submit');

        $this->assertDatabaseHas('souq_listings', [
            'user_id' => $this->member->id,
            'business_name' => 'New Business',
            'status' => 'pending',
        ]);
    }

    public function test_member_can_view_apply_form_route(): void
    {
        $this->actingAs($this->member)
            ->get('/souq/apply')
            ->assertOk()
            ->assertSee('List Your Business');
    }

    public function test_duplicate_application_prevented(): void
    {
        Livewire::actingAs($this->member)
            ->test(ApplyForm::class)
            ->set('businessName', 'Duplicate Business')
            ->set('category', 'services')
            ->set('description', 'A pending application for testing duplicate prevention.')
            ->set('contactEmail', 'duplicate@example.com')
            ->call('submit');

        Livewire::actingAs($this->member)
            ->test(ApplyForm::class)
            ->assertSet('hasPending', true)
            ->assertSee('under review');
    }

    public function test_wallet_shows_correct_balance(): void
    {
        CoinsService::award($this->member, 50, 'onboarding');

        $this->assertSame(50, CoinsService::getBalance($this->member));

        $this->actingAs($this->member)
            ->get('/wallet')
            ->assertRedirect('/profile?tab=wallet');

        Livewire::actingAs($this->member)
            ->test(WalletScreen::class)
            ->assertSee('50');
    }

    public function test_referral_link_contains_referral_code(): void
    {
        $this->assertNotNull($this->member->referral_code);

        Livewire::actingAs($this->member)
            ->test(WalletScreen::class)
            ->assertSee($this->member->referral_code);
    }

    public function test_admin_can_approve_souq_listing(): void
    {
        $listing = SouqListing::query()->create([
            'user_id' => $this->member->id,
            'business_name' => 'Approval Needed',
            'category' => 'education',
            'description' => 'Waiting for approval.',
            'contact_email' => 'approve@example.com',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin);

        SouqListingResource::approveListing($listing);

        $listing->refresh();

        $this->assertSame('approved', $listing->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'souq_approved',
            'auditable_id' => $listing->id,
        ]);
    }

    public function test_admin_can_load_souq_filament_resource(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/souq-listings')
            ->assertOk();
    }

    public function test_coins_balance_computed_from_ledger(): void
    {
        CoinsService::award($this->member, 50, 'onboarding');
        CoinsService::award($this->member, 25, 'referral');

        $this->assertSame(75, CoinsService::getBalance($this->member));

        CoinsService::deduct($this->member, 10, 'manual', 'Adjustment');

        $this->assertSame(65, CoinsService::getBalance($this->member));
    }

    protected function createOnboardedUser(string $email, string $referralCode, string $role): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => $referralCode,
        ]);

        $user->assignRole($role);
        $user->profile()->create([
            'display_name' => $user->name,
            'membership_status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        return $user;
    }
}
