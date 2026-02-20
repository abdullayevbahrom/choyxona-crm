<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorageWritable(),
        ];

        $failed = collect($checks)->contains(fn (bool $ok) => $ok === false);

        return response()->json(
            [
                'status' => $failed ? 'degraded' : 'ok',
                'timestamp' => now()->toIso8601String(),
                'checks' => $checks,
            ],
            $failed ? 503 : 200,
        );
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkStorageWritable(): bool
    {
        $path = storage_path('framework/cache');

        if (! is_dir($path)) {
            return @mkdir($path, 0755, true) || is_dir($path);
        }

        return is_writable($path);
    }
}
