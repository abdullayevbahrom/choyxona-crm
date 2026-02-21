<?php

namespace Tests\Feature;

use App\Jobs\GenerateActivityLogExport;
use App\Models\ActivityLogExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ActivityLogBackgroundExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_request_background_export_job(): void
    {
        Queue::fake();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->post('/activity-logs/exports', [
            'action' => 'orders.cancel',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('activity_log_exports', [
            'user_id' => $admin->id,
            'status' => ActivityLogExport::STATUS_PENDING,
        ]);

        Queue::assertPushed(GenerateActivityLogExport::class);
    }

    public function test_non_admin_cannot_request_background_export_job(): void
    {
        Queue::fake();

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->post('/activity-logs/exports');

        $response->assertForbidden();
        Queue::assertNothingPushed();
    }

    public function test_admin_can_poll_own_export_statuses(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $export = ActivityLogExport::query()->create([
            'user_id' => $admin->id,
            'status' => ActivityLogExport::STATUS_PROCESSING,
        ]);

        $response = $this->actingAs($admin)->get(
            "/activity-logs/exports/statuses?ids[]={$export->id}",
        );

        $response->assertOk();
        $response->assertJsonPath('exports.0.id', $export->id);
        $response->assertJsonPath(
            'exports.0.status',
            ActivityLogExport::STATUS_PROCESSING,
        );
    }

    public function test_ready_export_can_be_downloaded_from_legacy_storage_path(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $path = 'exports/activity-logs-legacy.csv';
        $absolutePath = storage_path('app/'.$path);
        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, "id,action\n1,test\n");

        $export = ActivityLogExport::query()->create([
            'user_id' => $admin->id,
            'status' => ActivityLogExport::STATUS_READY,
            'file_path' => $path,
            'file_size' => File::size($absolutePath),
            'finished_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(
            route('activity-logs.exports.download', $export),
        );

        $response->assertOk();
        $this->assertStringContainsString(
            'activity-logs-legacy.csv',
            (string) $response->headers->get('content-disposition'),
        );
    }
}
