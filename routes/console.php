<?php

use App\Console\Commands\BackupDatabase;
use App\Console\Commands\MonitorSystemHealth;
use App\Console\Commands\PruneActivityLogs;
use App\Console\Commands\PruneGeneratedExports;
use App\Console\Commands\RefreshReportDailySummaries;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(PruneActivityLogs::class)->dailyAt('03:00');
Schedule::command(BackupDatabase::class, ['--prune-days' => 30])->dailyAt(
    '02:30',
);
Schedule::command(RefreshReportDailySummaries::class, [
    '--days' => config('performance.report_summary_days', 400),
])->hourly();
Schedule::command(MonitorSystemHealth::class)->everyFiveMinutes();
Schedule::command(PruneGeneratedExports::class)->dailyAt('03:30');
