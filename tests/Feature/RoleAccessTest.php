<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_open_menu_page(): void
    {
        $cashier = User::factory()->create([
            "role" => User::ROLE_CASHIER,
        ]);

        $response = $this->actingAs($cashier)->get("/menu");

        $response->assertForbidden();
    }

    public function test_manager_can_open_menu_page(): void
    {
        $manager = User::factory()->create([
            "role" => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->get("/menu");

        $response->assertOk();
    }

    public function test_cashier_can_open_orders_history_page(): void
    {
        $cashier = User::factory()->create([
            "role" => User::ROLE_CASHIER,
        ]);

        $response = $this->actingAs($cashier)->get("/orders/history");

        $response->assertOk();
    }

    public function test_cashier_cannot_open_rooms_page(): void
    {
        $cashier = User::factory()->create([
            "role" => User::ROLE_CASHIER,
        ]);

        $response = $this->actingAs($cashier)->get("/rooms");

        $response->assertForbidden();
    }

    public function test_cashier_cannot_open_reports_page(): void
    {
        $cashier = User::factory()->create([
            "role" => User::ROLE_CASHIER,
        ]);

        $response = $this->actingAs($cashier)->get("/reports");

        $response->assertForbidden();
    }

    public function test_manager_can_open_reports_page(): void
    {
        $manager = User::factory()->create([
            "role" => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->get("/reports");

        $response->assertOk();
    }

    public function test_cashier_cannot_open_settings_page(): void
    {
        $cashier = User::factory()->create([
            "role" => User::ROLE_CASHIER,
        ]);

        $response = $this->actingAs($cashier)->get("/settings");

        $response->assertForbidden();
    }

    public function test_manager_can_open_settings_page(): void
    {
        $manager = User::factory()->create([
            "role" => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->get("/settings");

        $response->assertOk();
    }

    public function test_cashier_can_open_dashboard_cards_endpoint(): void
    {
        $cashier = User::factory()->create([
            "role" => User::ROLE_CASHIER,
        ]);

        $response = $this->actingAs($cashier)->get("/dashboard/cards");

        $response->assertOk();
    }

    public function test_cashier_can_open_order_live_endpoints(): void
    {
        $cashier = User::factory()->create([
            "role" => User::ROLE_CASHIER,
        ]);

        $room = \App\Models\Room::query()->create([
            "number" => "401",
            "status" => \App\Models\Room::STATUS_EMPTY,
            "is_active" => true,
        ]);

        $order = \App\Models\Order::query()->create([
            "room_id" => $room->id,
            "order_number" => "ORD-2026-9001",
            "status" => \App\Models\Order::STATUS_OPEN,
            "total_amount" => 0,
            "opened_at" => now(),
            "user_id" => $cashier->id,
        ]);

        $statusResponse = $this->actingAs($cashier)->get(
            "/orders/create/status?room={$room->id}",
        );
        $panelResponse = $this->actingAs($cashier)->get(
            "/orders/{$order->id}/panel",
        );
        $statusFingerprintResponse = $this->actingAs($cashier)->get(
            "/orders/create/status-fingerprint?room={$room->id}",
        );
        $panelFingerprintResponse = $this->actingAs($cashier)->get(
            "/orders/{$order->id}/panel-fingerprint",
        );
        $dashboardFingerprintResponse = $this->actingAs($cashier)->get(
            "/dashboard/fingerprint",
        );

        $statusResponse->assertOk();
        $panelResponse->assertOk();
        $statusFingerprintResponse->assertOk();
        $panelFingerprintResponse->assertOk();
        $dashboardFingerprintResponse->assertOk();
    }

    public function test_manager_cannot_open_activity_logs_page(): void
    {
        $manager = User::factory()->create([
            "role" => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->get("/activity-logs");
        $exportResponse = $this->actingAs($manager)->get(
            "/activity-logs/export.csv",
        );

        $response->assertForbidden();
        $exportResponse->assertForbidden();
    }

    public function test_admin_can_open_activity_logs_page(): void
    {
        $admin = User::factory()->create([
            "role" => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get("/activity-logs");

        $response->assertOk();
    }

    public function test_manager_cannot_open_users_page(): void
    {
        $manager = User::factory()->create([
            "role" => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->get("/users");

        $response->assertForbidden();
    }

    public function test_admin_can_open_users_page(): void
    {
        $admin = User::factory()->create([
            "role" => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get("/users");

        $response->assertOk();
    }
}
