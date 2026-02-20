<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportDailySummaryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_daily_summaries_command_populates_summary_table(): void
    {
        $room = Room::query()->create([
            'number' => '701',
            'status' => Room::STATUS_EMPTY,
            'is_active' => true,
        ]);

        $cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);

        $order = Order::query()->create([
            'room_id' => $room->id,
            'order_number' => 'ORD-2026-7001',
            'status' => Order::STATUS_CLOSED,
            'total_amount' => 25000,
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
            'user_id' => $cashier->id,
        ]);

        Bill::query()->create([
            'order_id' => $order->id,
            'room_id' => $room->id,
            'bill_number' => 'CHK-2026-7001',
            'subtotal' => 25000,
            'total_amount' => 25000,
            'is_printed' => true,
            'printed_at' => now(),
        ]);

        $this->artisan('reports:refresh-daily-summaries --days=30')
            ->expectsOutputToContain('Daily summaries refreshed')
            ->assertExitCode(0);

        $this->assertDatabaseHas('report_daily_summaries', [
            'day' => now()->toDateString(),
            'orders_count' => 1,
        ]);

        $this->assertDatabaseHas('report_daily_room_summaries', [
            'day' => now()->toDateString(),
            'room_id' => $room->id,
            'orders_count' => 1,
        ]);

        $this->assertDatabaseHas('report_daily_cashier_summaries', [
            'day' => now()->toDateString(),
            'cashier_id' => $cashier->id,
            'orders_count' => 1,
        ]);
    }

    public function test_reports_page_reads_from_summary_table_without_room_or_cashier_filters(): void
    {
        DB::table('report_daily_summaries')->insert([
            'day' => now()->toDateString(),
            'orders_count' => 3,
            'total_revenue' => 12345.67,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->get('/reports');

        $response->assertOk();
        $response->assertSee('12,345.67');
        $response->assertSee('3');
    }

    public function test_reports_page_falls_back_to_base_tables_when_summary_is_empty(): void
    {
        $room = Room::query()->create([
            'number' => '702',
            'status' => Room::STATUS_EMPTY,
            'is_active' => true,
        ]);

        $cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);

        $order = Order::query()->create([
            'room_id' => $room->id,
            'order_number' => 'ORD-2026-7002',
            'status' => Order::STATUS_CLOSED,
            'total_amount' => 55555,
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
            'user_id' => $cashier->id,
        ]);

        Bill::query()->create([
            'order_id' => $order->id,
            'room_id' => $room->id,
            'bill_number' => 'CHK-2026-7002',
            'subtotal' => 55555,
            'total_amount' => 55555,
            'is_printed' => true,
            'printed_at' => now(),
        ]);

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->get('/reports');

        $response->assertOk();
        $response->assertSee('55,555.00');
        $response->assertSee('1');
    }

    public function test_reports_summary_days_config_is_available(): void
    {
        config()->set('performance.report_summary_days', 123);

        $this->assertSame(123, config('performance.report_summary_days'));
    }

    public function test_reports_page_reads_from_room_summary_with_room_filter(): void
    {
        $room = Room::query()->create([
            'number' => '703',
            'status' => Room::STATUS_EMPTY,
            'is_active' => true,
        ]);

        DB::table('report_daily_room_summaries')->insert([
            'day' => now()->toDateString(),
            'room_id' => $room->id,
            'orders_count' => 4,
            'total_revenue' => 22222.22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->get(
            "/reports?room_id={$room->id}",
        );

        $response->assertOk();
        $response->assertSee('22,222.22');
        $response->assertSee('4');
    }

    public function test_reports_page_reads_from_cashier_summary_with_cashier_filter(): void
    {
        $cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);

        DB::table('report_daily_cashier_summaries')->insert([
            'day' => now()->toDateString(),
            'cashier_id' => $cashier->id,
            'orders_count' => 2,
            'total_revenue' => 11111.11,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->get(
            "/reports?cashier_id={$cashier->id}",
        );

        $response->assertOk();
        $response->assertSee('11,111.11');
        $response->assertSee('2');
    }
}
