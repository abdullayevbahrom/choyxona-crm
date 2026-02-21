<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWaiterHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_closed_order_history_shows_serving_waiters_and_bill_links(): void
    {
        $waiter = User::factory()->create([
            'name' => 'Ali Waiter',
            'role' => User::ROLE_WAITER,
        ]);
        $cashier = User::factory()->create([
            'name' => 'Vali Cashier',
            'role' => User::ROLE_CASHIER,
        ]);

        $room = Room::query()->create([
            'number' => '505',
            'status' => Room::STATUS_EMPTY,
            'is_active' => true,
        ]);

        $menuItem = MenuItem::query()->create([
            'name' => 'Choy',
            'type' => MenuItem::TYPE_DRINK,
            'price' => 12000,
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

        $this->assertDatabaseHas('order_waiters', [
            'order_id' => $order->id,
            'user_id' => $waiter->id,
        ]);

        $this->actingAs($cashier)->post(route('orders.bill.store', $order), [
            'payment_method' => 'cash',
        ])->assertRedirect();

        $bill = $order->fresh()->bill;
        $this->assertNotNull($bill);

        $this->actingAs($cashier)->post(route('bills.print', $bill))->assertRedirect(route('dashboard'));

        $historyResponse = $this->actingAs($cashier)->get(route('orders.history'));

        $historyResponse->assertOk();
        $historyResponse->assertSee('Ali Waiter');
        $historyResponse->assertSee(route('bills.show', $bill), false);
        $historyResponse->assertSee(route('bills.pdf', $bill), false);
    }
}
