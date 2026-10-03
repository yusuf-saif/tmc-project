<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegacyCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_member_profile_does_not_crash()
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get('/profile/legacy-card')
            ->assertOk();
    }

    public function test_user_without_member_profile_sees_fallback_message()
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get('/profile/legacy-card')
            ->assertSee('Membership details unavailable');
    }

    public function test_user_with_member_profile_sees_card()
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Aisha Cardholder',
            'membership_type' => 'M',
            'onboarding_status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/profile/legacy-card')
            ->assertOk()
            ->assertSee('Aisha Cardholder')
            ->assertSeeHtml('data-membership-type="M"')
            ->assertDontSee('TMC Member')
            ->assertDontSee('Membership details unavailable');
    }

    public function test_legacy_card_shows_membership_number()
    {
        $this->seed(RoleSeeder::class);

        $user = $this->cardholder(['membership_id' => 'TMC-M-1448-001']);

        $this->actingAs($user)
            ->get('/profile/legacy-card')
            ->assertOk()
            ->assertSee('TMC-M-1448-001')
            ->assertSee('Membership Number');
    }

    #[DataProvider('membershipTypeProvider')]
    public function test_legacy_card_shows_membership_number_for_every_tier(string $type, string $label)
    {
        $this->seed(RoleSeeder::class);

        $user = $this->cardholder([
            'membership_type' => $type,
            'membership_id' => "TMC-{$type}-1448-007",
        ]);

        $this->actingAs($user)
            ->get('/profile/legacy-card')
            ->assertOk()
            ->assertSee("TMC-{$type}-1448-007");
    }

    public function test_legacy_card_omits_membership_number_when_absent()
    {
        $this->seed(RoleSeeder::class);

        $user = $this->cardholder(['membership_id' => null]);

        $this->actingAs($user)
            ->get('/profile/legacy-card')
            ->assertOk()
            ->assertSee('Aisha Cardholder')
            ->assertDontSee('Membership Number');
    }

    public static function membershipTypeProvider(): array
    {
        return [
            'member' => ['M', 'Member'],
            'sixteen member' => ['SM', 'SixteenMember'],
            'executive' => ['E', 'Executive'],
        ];
    }

    protected function cardholder(array $overrides = []): User
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->memberProfile()->create(array_merge([
            'display_name' => 'Aisha Cardholder',
            'membership_type' => 'M',
            'onboarding_status' => 'active',
        ], $overrides));

        return $user->fresh();
    }
}
