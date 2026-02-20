<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_update_and_remove_order_item(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $room = Room::query()->create([
            'number' => '201',
            'status' => Room::STATUS_OCCUPIED,
            'is_active' => true,
        ]);

        $menuItem = MenuItem::query()->create([
            'name' => 'Choy',
            'type' => MenuItem::TYPE_DRINK,
            'price' => 5000,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'room_id' => $room->id,
            'order_number' => 'ORD-2026-1001',
            'status' => Order::STATUS_OPEN,
            'total_amount' => 10000,
            'opened_at' => now(),
            'user_id' => $user->id,
        ]);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
            'unit_price' => 5000,
            'subtotal' => 10000,
        ]);

        $updateResponse = $this->actingAs($user)->patch(route('orders.items.update', [$order, $item]), [
            'quantity' => 3,
        ]);

        $updateResponse->assertRedirect();
        $item->refresh();
        $order->refresh();

        $this->assertSame(3, $item->quantity);
        $this->assertSame('15000.00', $order->total_amount);

        $removeResponse = $this->actingAs($user)->delete(route('orders.items.destroy', [$order, $item]));

        $removeResponse->assertRedirect();
        $this->assertDatabaseMissing('order_items', ['id' => $item->id]);
        $this->assertSame('0.00', $order->fresh()->total_amount);
    }

    public function test_cashier_can_cancel_open_order_and_room_becomes_empty(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $room = Room::query()->create([
            'number' => '202',
            'status' => Room::STATUS_OCCUPIED,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'room_id' => $room->id,
            'order_number' => 'ORD-2026-1002',
            'status' => Order::STATUS_OPEN,
            'total_amount' => 0,
            'opened_at' => now(),
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('orders.cancel', $order));

        $response->assertRedirect(route('dashboard'));

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->closed_at);
        $this->assertSame(Room::STATUS_EMPTY, $room->fresh()->status);
    }
}
