<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_healthz_returns_ok_when_dependencies_are_available(): void
    {
        $response = $this->get(route('healthz'));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.storage', true);
    }

    public function test_healthz_returns_degraded_when_database_is_unavailable(): void
    {
        Config::set('database.default', 'missing_connection');

        $response = $this->get(route('healthz'));

        $response
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.database', false);
    }
}
