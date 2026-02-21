<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaiterBillingAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_waiter_cannot_manage_billing_actions_on_order_page(): void
    {
        $waiter = User::factory()->create([
            'role' => User::ROLE_WAITER,
        ]);

        $room = Room::query()->create([
            'number' => '403',
            'status' => Room::STATUS_EMPTY,
            'is_active' => true,
        ]);

        $this->actingAs($waiter)->post('/orders', [
            'room_id' => $room->id,
        ]);

        $order = Order::query()->where('room_id', $room->id)->firstOrFail();

        $showResponse = $this->actingAs($waiter)->get("/orders/{$order->id}");

        $showResponse->assertOk();
        $showResponse->assertSee(
            'Chek yaratish va buyurtmani bekor qilish uchun kassir yoki menejer huquqi kerak.',
        );
        $showResponse->assertDontSee(route('orders.bill.store', $order), false);
        $showResponse->assertDontSee(route('orders.cancel', $order), false);

        $this->actingAs($waiter)
            ->post("/orders/{$order->id}/bill", [
                'payment_method' => 'cash',
            ])
            ->assertForbidden();

        $this->actingAs($waiter)
            ->post("/orders/{$order->id}/cancel")
            ->assertForbidden();
    }
}
