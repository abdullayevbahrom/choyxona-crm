<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Room;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_cancel_writes_activity_log(): void
    {
        $user = User::factory()->create([
            "role" => User::ROLE_CASHIER,
        ]);

        $room = Room::query()->create([
            "number" => "801",
            "status" => Room::STATUS_OCCUPIED,
            "is_active" => true,
        ]);

        $order = Order::query()->create([
            "room_id" => $room->id,
            "order_number" => "ORD-2026-8001",
            "status" => Order::STATUS_OPEN,
            "total_amount" => 0,
            "opened_at" => now(),
            "user_id" => $user->id,
        ]);

        $response = $this->actingAs($user)->post(
            route("orders.cancel", $order),
        );

        $response->assertRedirect(route("dashboard"));

        $this->assertDatabaseHas("activity_logs", [
            "action" => "orders.cancel",
            "subject_type" => \App\Models\Order::class,
            "subject_id" => $order->id,
            "user_id" => $user->id,
        ]);
    }

    public function test_admin_can_export_activity_logs_csv(): void
    {
        $admin = User::factory()->create([
            "role" => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin);
        ActivityLogger::log("test.export", null, "CSV export test log", [
            "foo" => "bar",
        ]);

        $response = $this->actingAs($admin)->get("/activity-logs/export.csv");

        $response->assertOk();
        $response->assertHeader("content-type", "text/csv; charset=UTF-8");
    }

    public function test_admin_can_filter_activity_logs_by_subject_fields(): void
    {
        $admin = User::factory()->create([
            "role" => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin);
        ActivityLogger::log("subject.test", null, "Subject filter test", [
            "x" => 1,
        ]);

        $log = \App\Models\ActivityLog::query()->latest("id")->firstOrFail();
        $log->update([
            "subject_type" => \App\Models\Order::class,
            "subject_id" => 12345,
        ]);

        $response = $this->actingAs($admin)->get(
            "/activity-logs?subject_type=" .
                urlencode(\App\Models\Order::class) .
                "&subject_id=12345",
        );

        $response->assertOk();
        $response->assertSee("subject.test");
    }
}
