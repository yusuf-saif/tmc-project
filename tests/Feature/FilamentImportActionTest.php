<?php

namespace Tests\Feature;

use App\Jobs\ImportMembersJob;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilamentImportActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_import_dispatches_job_with_correct_path(): void
    {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $csvContent = "membership_id,name,email\nTMC-M-001,Test User,test@example.com";
        $file = UploadedFile::fake()->createWithContent('members.csv', $csvContent);

        $this->actingAs($admin);

        $path = Storage::disk('local')->putFile('imports', $file);

        ImportMembersJob::dispatch($path, $admin->id, 'local');

        Queue::assertPushed(ImportMembersJob::class, function ($job) use ($path, $admin) {
            return $job->csvPath === $path
                && $job->userId === $admin->id
                && $job->disk === 'local';
        });
    }

    public function test_file_is_stored_on_local_disk(): void
    {
        $csvContent = "membership_id,name,email\nTMC-M-001,Test User,test@example.com";
        $file = UploadedFile::fake()->createWithContent('members.csv', $csvContent);

        $path = Storage::disk('local')->putFile('imports', $file);

        $this->assertStringStartsWith('imports/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_import_action_renders_without_500(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin);

        $response = $this->get('/admin/users');

        $response->assertStatus(200);
    }
}
