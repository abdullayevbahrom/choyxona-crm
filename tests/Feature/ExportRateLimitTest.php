<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_export_endpoint_is_rate_limited(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($manager)->get('/reports/export.csv')->assertOk();
        }

        $response = $this->actingAs($manager)->get('/reports/export.csv');

        $response->assertStatus(429);
    }
}
