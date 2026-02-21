<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemWaiterAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_item_keeps_waiter_attribution(): void
    {
        $waiter = User::factory()->create([
            'name' => 'Hasan Ofitsiant',
            'role' => User::ROLE_WAITER,
        ]);

        $room = Room::query()->create([
            'number' => '611',
            'status' => Room::STATUS_EMPTY,
            'is_active' => true,
        ]);

        $menuItem = MenuItem::query()->create([
            'name' => 'Lagmon',
            'type' => MenuItem::TYPE_FOOD,
            'price' => 35000,
            'is_active' => true,
        ]);

        $this->actingAs($waiter)->post(route('orders.store'), [
            'room_id' => $room->id,
        ])->assertRedirect();

        $order = Order::query()->where('room_id', $room->id)->firstOrFail();

        $this->actingAs($waiter)->post(route('orders.items.store', $order), [
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
        ])->assertRedirect();

        $orderItem = $order->items()->firstOrFail();

        $this->assertDatabaseHas('order_item_waiters', [
            'order_item_id' => $orderItem->id,
            'user_id' => $waiter->id,
        ]);

        $this->actingAs($waiter)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Kiritgan ofitsiant(lar)')
            ->assertSee('Hasan Ofitsiant');
    }
}
