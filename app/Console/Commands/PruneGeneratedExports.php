<?php

namespace App\Console\Commands;

use App\Models\ActivityLogExport;
use App\Models\ReportExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneGeneratedExports extends Command
{
    protected $signature = 'exports:prune {--report-days= : Report export retention in days} {--activity-days= : Activity export retention in days}';

    protected $description = 'Delete old generated export files and records';

    public function handle(): int
    {
        $reportDays = (int) ($this->option('report-days') ?? config('exports.report_retention_days', 30));
        $activityDays = (int) ($this->option('activity-days') ?? config('exports.activity_retention_days', 30));

        if ($reportDays < 1 || $activityDays < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $reportResult = $this->pruneReportExports($reportDays);
        $activityResult = $this->pruneActivityExports($activityDays);

        $this->info(
            "Pruned report exports: {$reportResult['rows']} rows, {$reportResult['files']} files. ".
            "Pruned activity exports: {$activityResult['rows']} rows, {$activityResult['files']} files.",
        );

        return self::SUCCESS;
    }

    private function pruneReportExports(int $days): array
    {
        $cutoff = now()->subDays($days);

        $rows = ReportExport::query()
            ->whereIn('status', [ReportExport::STATUS_READY, ReportExport::STATUS_FAILED])
            ->where('created_at', '<', $cutoff)
            ->get(['id', 'file_path']);

        $deletedFiles = 0;

        foreach ($rows as $row) {
            if ($row->file_path && Storage::disk('local')->exists($row->file_path)) {
                Storage::disk('local')->delete($row->file_path);
                $deletedFiles++;
            }
        }

        $deletedRows = ReportExport::query()
            ->whereIn('status', [ReportExport::STATUS_READY, ReportExport::STATUS_FAILED])
            ->where('created_at', '<', $cutoff)
            ->delete();

        return ['rows' => $deletedRows, 'files' => $deletedFiles];
    }

    private function pruneActivityExports(int $days): array
    {
        $cutoff = now()->subDays($days);

        $rows = ActivityLogExport::query()
            ->whereIn('status', [ActivityLogExport::STATUS_READY, ActivityLogExport::STATUS_FAILED])
            ->where('created_at', '<', $cutoff)
            ->get(['id', 'file_path']);

        $deletedFiles = 0;

        foreach ($rows as $row) {
            if ($row->file_path && Storage::disk('local')->exists($row->file_path)) {
                Storage::disk('local')->delete($row->file_path);
                $deletedFiles++;
            }
        }

        $deletedRows = ActivityLogExport::query()
            ->whereIn('status', [ActivityLogExport::STATUS_READY, ActivityLogExport::STATUS_FAILED])
            ->where('created_at', '<', $cutoff)
            ->delete();

        return ['rows' => $deletedRows, 'files' => $deletedFiles];
    }
}
