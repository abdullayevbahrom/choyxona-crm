<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('observability.enabled', true)) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $response = $next($request);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($durationMs >= (int) config('observability.slow_request_ms', 700)) {
            Log::channel('performance')->warning('slow_request_detected', [
                'request_id' => $request->attributes->get('request_id'),
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $durationMs,
                'user_id' => optional($request->user())->id,
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
