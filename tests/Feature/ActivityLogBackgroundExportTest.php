<?php

namespace Tests\Feature;

use App\Jobs\GenerateActivityLogExport;
use App\Models\ActivityLogExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
