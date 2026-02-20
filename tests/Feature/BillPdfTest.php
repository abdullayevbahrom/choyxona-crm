<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_open_bill_pdf(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CASHIER]);
        Setting::query()->create(['company_name' => 'Choyxona CRM']);

        $room = Room::query()->create([
            'number' => '101',
            'status' => Room::STATUS_OCCUPIED,
            'is_active' => true,
        ]);

        $menuItem = MenuItem::query()->create([
            'name' => 'Palov',
            'type' => MenuItem::TYPE_FOOD,
            'price' => 30000,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'room_id' => $room->id,
            'order_number' => 'ORD-2026-0001',
            'status' => Order::STATUS_CLOSED,
            'total_amount' => 30000,
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
            'user_id' => $user->id,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'unit_price' => 30000,
            'subtotal' => 30000,
        ]);

        $bill = Bill::query()->create([
            'order_id' => $order->id,
            'room_id' => $room->id,
            'bill_number' => 'CHK-2026-0001',
            'subtotal' => 30000,
            'total_amount' => 30000,
            'is_printed' => false,
        ]);

        $response = $this->actingAs($user)->get(route('bills.pdf', $bill));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
