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

        $this->actingAs($waiter)
            ->post(route('orders.store'), [
                'room_id' => $room->id,
            ])
            ->assertRedirect();

        $order = Order::query()->where('room_id', $room->id)->firstOrFail();

        $this->actingAs($waiter)
            ->post(route('orders.items.store', $order), [
                'menu_item_id' => $menuItem->id,
                'quantity' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_waiters', [
            'order_id' => $order->id,
            'user_id' => $waiter->id,
        ]);

        $this->actingAs($cashier)
            ->post(route('orders.bill.store', $order), [
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $bill = $order->fresh()->bill;
        $this->assertNotNull($bill);

        $this->actingAs($cashier)
            ->post(route('bills.print', $bill))
            ->assertRedirect(route('dashboard'));

        $historyResponse = $this->actingAs($cashier)->get(
            route('orders.history'),
        );

        $historyResponse->assertOk();
        $historyResponse->assertSee('Ali Waiter');
        $historyResponse->assertSee(route('bills.show', $bill), false);
        $historyResponse->assertSee(route('bills.pdf', $bill), false);
    }

    public function test_open_order_history_hides_cashier_and_merges_item_waiters(): void
    {
        $waiterOne = User::factory()->create([
            'name' => 'Waiter One',
            'role' => User::ROLE_WAITER,
        ]);
        $waiterTwo = User::factory()->create([
            'name' => 'Waiter Two',
            'role' => User::ROLE_WAITER,
        ]);
        $creatorCashier = User::factory()->create([
            'name' => 'Creator Cashier',
            'role' => User::ROLE_CASHIER,
        ]);
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $room = Room::query()->create([
            'number' => '506',
            'status' => Room::STATUS_EMPTY,
            'is_active' => true,
        ]);

        $menuItem = MenuItem::query()->create([
            'name' => 'Mastava',
            'type' => MenuItem::TYPE_FOOD,
            'price' => 25000,
            'is_active' => true,
        ]);

        $this->actingAs($waiterOne)
            ->post(route('orders.store'), [
                'room_id' => $room->id,
            ])
            ->assertRedirect();

        $order = Order::query()->where('room_id', $room->id)->firstOrFail();
        $order->update(['user_id' => $creatorCashier->id]);

        $this->actingAs($waiterOne)
            ->post(route('orders.items.store', $order), [
                'menu_item_id' => $menuItem->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $item = $order->items()->firstOrFail();
        $item->waiters()->syncWithoutDetaching([$waiterTwo->id]);

        $historyResponse = $this->actingAs($manager)->get(
            route('orders.history'),
        );

        $historyResponse->assertOk();
        $historyResponse->assertSee('Waiter One');
        $historyResponse->assertSee('Waiter Two');
        $historyResponse->assertDontSee('Creator Cashier');
    }
}
