<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_healthz_returns_ok_when_dependencies_are_available(): void
    {
        $response = $this->get(route("healthz"));

        $response
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("checks.database", true)
            ->assertJsonPath("checks.storage", true);

        $this->assertIsBool($response->json("checks.queue_backlog"));
        $this->assertIsBool($response->json("checks.disk_free"));
    }

    public function test_healthz_returns_degraded_when_database_is_unavailable(): void
    {
        Config::set("database.default", "missing_connection");

        $response = $this->get(route("healthz"));

        $response
            ->assertStatus(503)
            ->assertJsonPath("status", "degraded")
            ->assertJsonPath("checks.database", false);
    }

    public function test_healthz_exposes_queue_backlog_check_without_failing_status(): void
    {
        config()->set("monitoring.queue_backlog_threshold", -1);

        $response = $this->get(route("healthz"));

        $response->assertOk()->assertJsonPath("status", "ok");

        $this->assertIsBool($response->json("checks.queue_backlog"));
    }
}
