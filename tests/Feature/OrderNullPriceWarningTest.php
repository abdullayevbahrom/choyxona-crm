<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderNullPriceWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_with_null_price_can_be_added_with_warning(): void
    {
        $cashier = User::factory()->create([
            'role' => User::ROLE_CASHIER,
        ]);

        $room = Room::query()->create([
            'number' => '909',
            'status' => Room::STATUS_OCCUPIED,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'room_id' => $room->id,
            'order_number' => 'ORD-2026-9090',
            'status' => Order::STATUS_OPEN,
            'total_amount' => 0,
            'opened_at' => now(),
            'user_id' => $cashier->id,
        ]);

        $menuItem = MenuItem::query()->create([
            'name' => 'Test item without price',
            'type' => MenuItem::TYPE_FOOD,
            'price' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->post(
            route('orders.items.store', $order),
            [
                'menu_item_id' => $menuItem->id,
                'quantity' => 2,
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
            'unit_price' => 0,
            'subtotal' => 0,
        ]);
    }
}
