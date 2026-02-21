<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_history_can_filter_by_serving_staff(): void
    {
        $cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);
        $staffA = User::factory()->create([
            'name' => 'Staff A',
            'role' => User::ROLE_WAITER,
        ]);
        $staffB = User::factory()->create([
            'name' => 'Staff B',
            'role' => User::ROLE_WAITER,
        ]);

        $roomA = Room::query()->create([
            'number' => '201',
            'status' => Room::STATUS_OCCUPIED,
            'is_active' => true,
        ]);
        $roomB = Room::query()->create([
            'number' => '202',
            'status' => Room::STATUS_OCCUPIED,
            'is_active' => true,
        ]);

        $orderA = Order::query()->create([
            'room_id' => $roomA->id,
            'order_number' => 'ORD-FILTER-0001',
            'status' => Order::STATUS_OPEN,
            'total_amount' => 10000,
            'opened_at' => now(),
            'user_id' => $cashier->id,
        ]);
        $orderA->waiters()->syncWithoutDetaching([$staffA->id]);

        $orderB = Order::query()->create([
            'room_id' => $roomB->id,
            'order_number' => 'ORD-FILTER-0002',
            'status' => Order::STATUS_OPEN,
            'total_amount' => 20000,
            'opened_at' => now(),
            'user_id' => $cashier->id,
        ]);
        $orderB->waiters()->syncWithoutDetaching([$staffB->id]);

        $response = $this->actingAs($cashier)->get(
            route('orders.history', ['staff_id' => $staffA->id]),
        );

        $response->assertOk();
        $response->assertSee('ORD-FILTER-0001');
        $response->assertDontSee('ORD-FILTER-0002');
    }

    public function test_rooms_index_supports_number_name_capacity_status_and_active_filters(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        Room::query()->create([
            'number' => '301',
            'name' => 'VIP xona',
            'capacity' => 6,
            'status' => Room::STATUS_OCCUPIED,
            'is_active' => true,
        ]);
        Room::query()->create([
            'number' => '302',
            'name' => 'Oddiy xona',
            'capacity' => 4,
            'status' => Room::STATUS_EMPTY,
            'is_active' => false,
        ]);

        $response = $this->actingAs($manager)->get(route('rooms.index', [
            'number' => '301',
            'name' => 'VIP',
            'capacity' => 6,
            'status' => Room::STATUS_OCCUPIED,
            'is_active' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('301');
        $response->assertDontSee('302');
    }

    public function test_users_index_supports_name_email_and_role_filters(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Main Admin',
            'email' => 'main-admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Cashier One',
            'email' => 'cashier-one@example.com',
            'role' => User::ROLE_CASHIER,
        ]);
        User::factory()->create([
            'name' => 'Manager One',
            'email' => 'manager-one@example.com',
            'role' => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($admin)->get(route('users.index', [
            'name' => 'Cashier',
            'email' => 'cashier-one',
            'role' => User::ROLE_CASHIER,
        ]));

        $response->assertOk();
        $response->assertSee('Cashier One');
        $response->assertDontSee('Manager One');
    }
}
