<?php

namespace Tests\Feature;

use App\Models\ActivityLogExport;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruneGeneratedExportsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_generated_exports_removes_old_files_and_rows(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $oldReportPath = 'exports/reports-old.csv';
        $newReportPath = 'exports/reports-new.csv';
        $oldActivityPath = 'exports/activity-old.csv';
        $newActivityPath = 'exports/activity-new.csv';

        Storage::disk('local')->put($oldReportPath, 'old');
        Storage::disk('local')->put($newReportPath, 'new');
        Storage::disk('local')->put($oldActivityPath, 'old');
        Storage::disk('local')->put($newActivityPath, 'new');

        $oldReport = ReportExport::query()->create([
            'user_id' => $user->id,
            'status' => ReportExport::STATUS_READY,
            'filters' => [],
            'format' => 'csv',
            'file_path' => $oldReportPath,
            'file_size' => 3,
            'finished_at' => now()->subDays(100),
        ]);

        $newReport = ReportExport::query()->create([
            'user_id' => $user->id,
            'status' => ReportExport::STATUS_READY,
            'filters' => [],
            'format' => 'csv',
            'file_path' => $newReportPath,
            'file_size' => 3,
            'finished_at' => now()->subDays(5),
        ]);

        $oldActivity = ActivityLogExport::query()->create([
            'user_id' => $user->id,
            'status' => ActivityLogExport::STATUS_READY,
            'filters' => [],
            'file_path' => $oldActivityPath,
            'file_size' => 3,
            'finished_at' => now()->subDays(100),
        ]);

        $newActivity = ActivityLogExport::query()->create([
            'user_id' => $user->id,
            'status' => ActivityLogExport::STATUS_READY,
            'filters' => [],
            'file_path' => $newActivityPath,
            'file_size' => 3,
            'finished_at' => now()->subDays(5),
        ]);

        ReportExport::query()->whereKey($oldReport->id)->update([
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);
        ReportExport::query()->whereKey($newReport->id)->update([
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        ActivityLogExport::query()->whereKey($oldActivity->id)->update([
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);
        ActivityLogExport::query()->whereKey($newActivity->id)->update([
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $this->artisan('exports:prune --report-days=30 --activity-days=30')
            ->expectsOutputToContain('Pruned report exports: 1 rows')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('report_exports', ['id' => $oldReport->id]);
        $this->assertDatabaseHas('report_exports', ['id' => $newReport->id]);

        $this->assertDatabaseMissing('activity_log_exports', ['id' => $oldActivity->id]);
        $this->assertDatabaseHas('activity_log_exports', ['id' => $newActivity->id]);

        Storage::disk('local')->assertMissing($oldReportPath);
        Storage::disk('local')->assertExists($newReportPath);
        Storage::disk('local')->assertMissing($oldActivityPath);
        Storage::disk('local')->assertExists($newActivityPath);
    }
}
