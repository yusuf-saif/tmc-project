<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use App\Services\CoinsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class BadgeAwardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function member(): User
    {
        $user = User::factory()->create([
            'email' => 'badge-member-'.Str::random(8).'@test.com',
            'email_verified_at' => now(),
        ]);

        $user->assignRole('member');

        return $user;
    }

    protected function admin(): User
    {
        $user = User::factory()->create([
            'email' => 'badge-admin-'.Str::random(8).'@test.com',
            'email_verified_at' => now(),
        ]);

        $user->assignRole('super_admin');

        return $user;
    }

    protected function badge(int $coinReward = 0): Badge
    {
        return Badge::create([
            'name' => 'Badge '.Str::random(6),
            'description' => 'A test badge',
            'criteria' => 'Testing badge awards',
            'coin_reward' => $coinReward,
            'is_active' => true,
        ]);
    }

    protected function award(User $user, Badge $badge, ?User $awarder = null): void
    {
        UserBadge::create([
            'user_id' => $user->id,
            'badge_id' => $badge->id,
            'awarded_at' => now(),
            'awarded_by' => $awarder?->id,
        ]);
    }

    public function test_award_badge_creates_row_and_grants_coins(): void
    {
        $user = $this->member();
        $badge = $this->badge(coinReward: 50);

        $this->actingAs($this->admin());

        $this->assertTrue(UserResource::awardBadge($user, $badge->id));

        $this->assertDatabaseCount('user_badges', 1);
        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);
        $this->assertSame(50, CoinsService::getBalance($user));
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'badge_awarded')->count());
    }

    public function test_second_award_of_same_badge_is_blocked_without_duplicate(): void
    {
        $user = $this->member();
        $badge = $this->badge(coinReward: 50);

        $this->actingAs($this->admin());

        $this->assertTrue(UserResource::awardBadge($user, $badge->id));
        $this->assertFalse(UserResource::awardBadge($user, $badge->id));

        $this->assertDatabaseCount('user_badges', 1);
        $this->assertSame(50, CoinsService::getBalance($user));
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'badge_awarded')->count());
    }

    public function test_unique_constraint_blocks_duplicate_rows(): void
    {
        $user = $this->member();
        $badge = $this->badge();

        $this->award($user, $badge);

        $this->expectException(QueryException::class);

        $this->award($user, $badge);
    }

    public function test_badge_options_exclude_already_held_badges(): void
    {
        $user = $this->member();
        $held = $this->badge();
        $available = $this->badge();
        $inactive = Badge::create([
            'name' => 'Inactive '.Str::random(6),
            'description' => 'An inactive badge',
            'criteria' => 'Testing',
            'coin_reward' => 0,
            'is_active' => false,
        ]);

        $this->award($user, $held);

        $options = UserResource::badgeOptions($user);

        $this->assertArrayNotHasKey($held->id, $options);
        $this->assertArrayHasKey($available->id, $options);
        $this->assertArrayNotHasKey($inactive->id, $options);
    }

    public function test_award_badge_action_is_hidden_when_member_holds_all_active_badges(): void
    {
        $user = $this->member();
        $badge = $this->badge();

        $this->award($user, $badge);

        $this->actingAs($this->admin());

        Livewire::test(ViewUser::class, ['record' => $user->getKey()])
            ->assertActionHidden('awardBadge');
    }

    public function test_award_badge_action_grants_badge_and_notifies(): void
    {
        $user = $this->member();
        $badge = $this->badge(coinReward: 25);

        $this->actingAs($this->admin());

        Livewire::test(ViewUser::class, ['record' => $user->getKey()])
            ->assertActionVisible('awardBadge')
            ->callAction('awardBadge', ['badge_id' => $badge->id])
            ->assertNotified();

        $this->assertDatabaseHas('user_badges', [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);
        $this->assertSame(25, CoinsService::getBalance($user));
    }
}
