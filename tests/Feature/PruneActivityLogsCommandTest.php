<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneActivityLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_command_deletes_logs_older_than_threshold(): void
    {
        $old = ActivityLog::query()->create([
            'action' => 'old.log',
        ]);

        $new = ActivityLog::query()->create([
            'action' => 'new.log',
        ]);

        ActivityLog::query()->whereKey($old->id)->update([
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
        ]);

        ActivityLog::query()->whereKey($new->id)->update([
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $this->artisan('activity-logs:prune --days=90')
            ->expectsOutputToContain('Pruned 1 activity logs')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'old.log',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'new.log',
        ]);
    }
}
