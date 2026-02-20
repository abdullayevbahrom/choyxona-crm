<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorSystemHealth extends Command
{
    protected $signature = "monitor:system-health";

    protected $description = "Check queue backlog, failed jobs and report summary freshness";

    public function handle(): int
    {
        $failedJobsThreshold = (int) config("monitoring.failed_jobs_threshold", 5);
        $queueBacklogThreshold = (int) config("monitoring.queue_backlog_threshold", 200);
        $summaryStaleHours = (int) config("monitoring.summary_stale_hours", 2);

        $failedJobsCount = $this->safeCount("failed_jobs");
        $queueBacklogCount = $this->safeCount("jobs");

        $lastSummaryDay = DB::table("report_daily_summaries")->max("day");
        $isSummaryStale = false;

        if ($lastSummaryDay !== null) {
            $hoursSinceSummary = Carbon::parse((string) $lastSummaryDay)
                ->endOfDay()
                ->diffInHours(now());
            $isSummaryStale = $hoursSinceSummary > $summaryStaleHours;
        }

        $alerts = [];

        if ($failedJobsCount > $failedJobsThreshold) {
            $alerts[] = "failed_jobs={$failedJobsCount} > {$failedJobsThreshold}";
        }

        if ($queueBacklogCount > $queueBacklogThreshold) {
            $alerts[] = "queue_backlog={$queueBacklogCount} > {$queueBacklogThreshold}";
        }

        if ($isSummaryStale) {
            $alerts[] = "report_daily_summaries is stale (last_day={$lastSummaryDay})";
        }

        if ($alerts !== []) {
            Log::channel("alerts")->warning("System health degraded", [
                "failed_jobs_count" => $failedJobsCount,
                "failed_jobs_threshold" => $failedJobsThreshold,
                "queue_backlog_count" => $queueBacklogCount,
                "queue_backlog_threshold" => $queueBacklogThreshold,
                "summary_last_day" => $lastSummaryDay,
                "summary_stale_hours" => $summaryStaleHours,
                "alerts" => $alerts,
            ]);

            $this->error("DEGRADED: " . implode("; ", $alerts));

            return self::FAILURE;
        }

        $this->info("HEALTHY: monitoring checks passed");

        return self::SUCCESS;
    }

    private function safeCount(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
