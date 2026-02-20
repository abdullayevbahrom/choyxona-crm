<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            "database" => $this->checkDatabase(),
            "storage" => $this->checkStorageWritable(),
            "queue_backlog" => $this->checkQueueBacklog(),
            "disk_free" => $this->checkDiskFreeSpace(),
        ];

        $criticalChecks = [
            "database" => $checks["database"],
            "storage" => $checks["storage"],
        ];
        $failed = collect($criticalChecks)->contains(
            fn(bool $ok) => $ok === false,
        );

        return response()->json(
            [
                "status" => $failed ? "degraded" : "ok",
                "timestamp" => now()->toIso8601String(),
                "checks" => $checks,
            ],
            $failed ? 503 : 200,
        );
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select("SELECT 1");

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkStorageWritable(): bool
    {
        $path = storage_path("framework/cache");

        if (!is_dir($path)) {
            return @mkdir($path, 0755, true) || is_dir($path);
        }

        return is_writable($path);
    }

    private function checkQueueBacklog(): bool
    {
        $threshold = (int) config("monitoring.queue_backlog_threshold", 200);

        try {
            $count = (int) DB::table("jobs")->count();

            return $count <= $threshold;
        } catch (\Throwable) {
            return true;
        }
    }

    private function checkDiskFreeSpace(): bool
    {
        $thresholdPercent = (float) config(
            "monitoring.min_disk_free_percent",
            5,
        );
        $root = storage_path();
        $total = @disk_total_space($root);
        $free = @disk_free_space($root);

        if ($total === false || $total <= 0 || $free === false) {
            return false;
        }

        $freePercent = ($free / $total) * 100;

        return $freePercent >= $thresholdPercent;
    }
}
