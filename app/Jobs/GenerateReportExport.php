<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Services\ReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $exportId) {}

    public function handle(ReportService $reportService): void
    {
        $export = ReportExport::query()->find($this->exportId);

        if (! $export || $export->status !== ReportExport::STATUS_PENDING) {
            return;
        }

        $export->update([
            'status' => ReportExport::STATUS_PROCESSING,
            'started_at' => now(),
            'error_message' => null,
        ]);

        $tmpPath = tempnam(sys_get_temp_dir(), 'report-export-');

        if ($tmpPath === false) {
            $export->update([
                'status' => ReportExport::STATUS_FAILED,
                'error_message' => 'Temp fayl yaratilmadi.',
                'finished_at' => now(),
            ]);

            return;
        }

        try {
            $tmpHandle = fopen($tmpPath, 'w');

            if ($tmpHandle === false) {
                throw new \RuntimeException('Temp fayl ochilmadi.');
            }

            $reportService->streamCsv($export->filters ?? [], $tmpHandle);
            fclose($tmpHandle);

            $path =
                "exports/reports-{$export->id}-".
                now()->format('Ymd-His').
                '.csv';
            $readHandle = fopen($tmpPath, 'r');

            if ($readHandle === false) {
                throw new \RuntimeException("Temp fayl o'qilmadi.");
            }

            Storage::disk('local')->writeStream($path, $readHandle);
            fclose($readHandle);
            $this->ensureExportPathIsReadable($path);

            $fileSize = filesize($tmpPath) ?: null;

            $export->update([
                'status' => ReportExport::STATUS_READY,
                'file_path' => $path,
                'file_size' => $fileSize,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $export->update([
                'status' => ReportExport::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        } finally {
            @unlink($tmpPath);
        }
    }

    private function ensureExportPathIsReadable(string $path): void
    {
        $disk = Storage::disk('local');

        try {
            $disk->setVisibility($path, 'public');
        } catch (\Throwable) {
            // Best effort only; download fallback will handle issues.
        }

        try {
            $absolutePath = $disk->path($path);
            @chmod(dirname($absolutePath), 0755);
            @chmod($absolutePath, 0644);
        } catch (\Throwable) {
            // Best effort only.
        }
    }
}
