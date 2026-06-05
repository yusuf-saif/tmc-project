<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\User;
use App\Livewire\Journal\JournalScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class JournalPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected User $member;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->member = $this->createMember('member@example.com', 'MEMB1001');
        $this->admin = User::factory()->create([
            'status' => 'active',
            'referral_code' => 'ADMIN100',
        ]);
        $this->admin->assignRole('super_admin');
    }

    public function test_journal_entry_saves_with_encrypted_body(): void
    {
        JournalEntry::query()->create([
            'user_id' => $this->member->id,
            'entry_date' => now()->toDateString(),
            'mood' => 'grateful',
            'body' => 'my secret',
        ]);

        $this->assertDatabaseHas('journal_entries', ['user_id' => $this->member->id]);

        $raw = DB::table('journal_entries')->where('user_id', $this->member->id)->first()->body;
        $this->assertStringNotContainsString('my secret', $raw);
    }

    public function test_member_can_read_edit_and_delete_own_entries(): void
    {
        $entry = JournalEntry::query()->create([
            'user_id' => $this->member->id,
            'entry_date' => now()->toDateString(),
            'mood' => 'happy',
            'body' => 'my secret',
        ]);

        $this->assertSame('my secret', $entry->body);

        Livewire::actingAs($this->member)
            ->test(JournalScreen::class)
            ->call('openEditEntry', $entry->id)
            ->set('entryDate', now()->subDay()->toDateString())
            ->set('mood', 'grateful')
            ->set('body', 'updated secret')
            ->call('saveEntry')
            ->call('deleteEntry', $entry->id);

        $this->assertSoftDeleted('journal_entries', ['id' => $entry->id]);
    }

    public function test_member_cannot_read_another_members_entry(): void
    {
        $memberB = $this->createMember('memberb@example.com', 'MEMB1002');
        $entry = JournalEntry::query()->create([
            'user_id' => $this->member->id,
            'entry_date' => now()->toDateString(),
            'mood' => 'neutral',
            'body' => 'member secret',
        ]);

        $this->assertFalse($memberB->can('view', $entry));
    }

    public function test_admin_cannot_read_journal_body(): void
    {
        $entry = JournalEntry::query()->create([
            'user_id' => $this->member->id,
            'entry_date' => now()->toDateString(),
            'mood' => 'reflective',
            'body' => 'admin cannot read this',
        ]);

        $this->assertFalse($this->admin->can('view', $entry));
    }

    public function test_admin_cannot_access_journal_via_http(): void
    {
        $this->admin->profile()->create([
            'display_name' => $this->admin->name,
            'onboarding_completed_at' => now(),
        ]);

        JournalEntry::query()->create([
            'user_id' => $this->member->id,
            'entry_date' => now()->toDateString(),
            'mood' => 'anxious',
            'body' => 'top secret body',
        ]);

        $response = $this->actingAs($this->admin)->get('/journal');

        $response->assertForbidden();
        $response->assertDontSee('top secret body');
    }

    public function test_encrypted_cast_is_applied(): void
    {
        JournalEntry::query()->create([
            'user_id' => $this->member->id,
            'entry_date' => now()->toDateString(),
            'mood' => 'grateful',
            'body' => 'plain text value',
        ]);

        $raw = DB::table('journal_entries')->first()->body;
        $this->assertNotEquals('plain text value', $raw);
    }

    protected function createMember(string $email, string $referralCode): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'status' => 'active',
            'referral_code' => $referralCode,
        ]);
        $user->assignRole('member');
        $user->profile()->create([
            'display_name' => $user->name,
            'onboarding_completed_at' => now(),
        ]);

        return $user;
    }
}
