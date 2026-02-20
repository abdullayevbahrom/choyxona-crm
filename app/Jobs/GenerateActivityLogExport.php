<?php

namespace App\Jobs;

use App\Models\ActivityLogExport;
use App\Services\ActivityLogQueryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateActivityLogExport implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $exportId)
    {
    }

    public function handle(ActivityLogQueryService $queryService): void
    {
        $export = ActivityLogExport::query()->find($this->exportId);

        if (! $export || $export->status !== ActivityLogExport::STATUS_PENDING) {
            return;
        }

        $export->update([
            'status' => ActivityLogExport::STATUS_PROCESSING,
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {
            $path = 'exports/activity-logs-'.$export->id.'-'.now()->format('Ymd-His').'.csv';

            $stream = fopen('php://temp', 'r+');

            fputcsv($stream, [
                'id',
                'created_at',
                'user',
                'action',
                'subject_type',
                'subject_id',
                'description',
                'ip_address',
                'properties',
            ]);

            $queryService->build($export->filters ?? [])->chunk(500, function ($logs) use ($stream) {
                foreach ($logs as $log) {
                    fputcsv($stream, [
                        $log->id,
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->user?->name,
                        $log->action,
                        $log->subject_type,
                        $log->subject_id,
                        $log->description,
                        $log->ip_address,
                        json_encode($log->properties, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });

            rewind($stream);
            $content = stream_get_contents($stream) ?: '';
            fclose($stream);

            Storage::disk('local')->put($path, $content);

            $export->update([
                'status' => ActivityLogExport::STATUS_READY,
                'file_path' => $path,
                'file_size' => strlen($content),
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $export->update([
                'status' => ActivityLogExport::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }
}
