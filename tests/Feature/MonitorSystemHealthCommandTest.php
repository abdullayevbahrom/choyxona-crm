<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonitorSystemHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_command_returns_success_when_metrics_are_healthy(): void
    {
        config()->set("monitoring.failed_jobs_threshold", 5);
        config()->set("monitoring.queue_backlog_threshold", 200);
        config()->set("monitoring.summary_stale_hours", 48);

        $this->artisan("monitor:system-health")
            ->expectsOutputToContain("HEALTHY")
            ->assertExitCode(0);
    }

    public function test_monitor_command_returns_failure_when_thresholds_are_exceeded(): void
    {
        DB::table("failed_jobs")->insert([
            "uuid" => (string) Str::uuid(),
            "connection" => "database",
            "queue" => "default",
            "payload" => "{}",
            "exception" => "Test exception",
            "failed_at" => now(),
        ]);

        DB::table("jobs")->insert([
            "queue" => "default",
            "payload" => "{}",
            "attempts" => 0,
            "reserved_at" => null,
            "available_at" => now()->timestamp,
            "created_at" => now()->timestamp,
        ]);

        config()->set("monitoring.failed_jobs_threshold", 0);
        config()->set("monitoring.queue_backlog_threshold", 0);

        $this->artisan("monitor:system-health")
            ->expectsOutputToContain("DEGRADED")
            ->assertExitCode(1);
    }
}
