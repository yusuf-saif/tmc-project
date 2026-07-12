<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BackfillInvitedAtSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillInvitedAtSeederTest extends TestCase
{
    use RefreshDatabase;

    private string $backfillPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backfillPath = storage_path('app/invited_backfill.txt');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->backfillPath)) {
            unlink($this->backfillPath);
        }

        parent::tearDown();
    }

    private function writeBackfillFile(string $content): void
    {
        file_put_contents($this->backfillPath, $content);
    }

    public function test_updates_matching_users_and_reports_unknown(): void
    {
        $user1 = User::factory()->create(['email' => 'alice@example.com', 'invited_at' => null]);
        $user2 = User::factory()->create(['email' => 'bob@example.com', 'invited_at' => null]);
        $user3 = User::factory()->create(['email' => 'carol@example.com', 'invited_at' => null]);

        // Already invited — should not be touched
        User::factory()->create(['email' => 'dave@example.com', 'invited_at' => now()]);

        $this->writeBackfillFile("alice@example.com\nbob@example.com\ncarol@example.com\nunknown@example.com\n");

        $this->seed(BackfillInvitedAtSeeder::class);

        $user1->refresh();
        $user2->refresh();
        $user3->refresh();
        $this->assertNotNull($user1->invited_at);
        $this->assertNotNull($user2->invited_at);
        $this->assertNotNull($user3->invited_at);

        $dave = User::where('email', 'dave@example.com')->first();
        $this->assertNotNull($dave->invited_at);
    }

    public function test_does_not_touch_users_without_matching_email(): void
    {
        $user = User::factory()->create(['email' => 'notincluded@example.com', 'invited_at' => null]);

        $this->writeBackfillFile("alice@example.com\n");

        $this->seed(BackfillInvitedAtSeeder::class);

        $user->refresh();
        $this->assertNull($user->invited_at);
    }

    public function test_handles_empty_file_gracefully(): void
    {
        $this->writeBackfillFile('');

        $this->seed(BackfillInvitedAtSeeder::class);

        $this->assertEquals(0, User::whereNotNull('invited_at')->count());
    }

    public function test_skips_blank_lines_in_file(): void
    {
        $user = User::factory()->create(['email' => 'alice@example.com', 'invited_at' => null]);

        $this->writeBackfillFile("\n\nalice@example.com\n\n\n");

        $this->seed(BackfillInvitedAtSeeder::class);

        $user->refresh();
        $this->assertNotNull($user->invited_at);
    }
}
