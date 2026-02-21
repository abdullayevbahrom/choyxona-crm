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

    public function test_bill_show_uses_logo_fallback_and_configured_size(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CASHIER]);
        Setting::query()->create([
            'company_name' => 'Choyxona CRM',
            'notification_logo_url' => null,
            'notification_logo_size' => 40,
        ]);

        $room = Room::query()->create([
            'number' => '102',
            'status' => Room::STATUS_OCCUPIED,
            'is_active' => true,
        ]);

        $menuItem = MenuItem::query()->create([
            'name' => 'Lagmon',
            'type' => MenuItem::TYPE_FOOD,
            'price' => 35000,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'room_id' => $room->id,
            'order_number' => 'ORD-2026-0002',
            'status' => Order::STATUS_CLOSED,
            'total_amount' => 35000,
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
            'user_id' => $user->id,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'unit_price' => 35000,
            'subtotal' => 35000,
        ]);

        $bill = Bill::query()->create([
            'order_id' => $order->id,
            'room_id' => $room->id,
            'bill_number' => 'CHK-2026-0002',
            'subtotal' => 35000,
            'total_amount' => 35000,
            'is_printed' => false,
        ]);

        $response = $this->actingAs($user)->get(route('bills.show', $bill));

        $response->assertOk();
        $response->assertSee('favicon.svg');
        $response->assertSee('width="40"', false);
        $response->assertSee('height="40"', false);
    }

    public function test_bill_show_uses_custom_logo_url_when_configured(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CASHIER]);
        Setting::query()->create([
            'company_name' => 'Choyxona CRM',
            'notification_logo_url' => 'https://cdn.example.com/logo.png',
            'notification_logo_size' => 24,
        ]);

        $room = Room::query()->create([
            'number' => '103',
            'status' => Room::STATUS_OCCUPIED,
            'is_active' => true,
        ]);

        $menuItem = MenuItem::query()->create([
            'name' => 'Shurva',
            'type' => MenuItem::TYPE_FOOD,
            'price' => 28000,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'room_id' => $room->id,
            'order_number' => 'ORD-2026-0003',
            'status' => Order::STATUS_CLOSED,
            'total_amount' => 28000,
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
            'user_id' => $user->id,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'unit_price' => 28000,
            'subtotal' => 28000,
        ]);

        $bill = Bill::query()->create([
            'order_id' => $order->id,
            'room_id' => $room->id,
            'bill_number' => 'CHK-2026-0003',
            'subtotal' => 28000,
            'total_amount' => 28000,
            'is_printed' => false,
        ]);

        $response = $this->actingAs($user)->get(route('bills.show', $bill));

        $response->assertOk();
        $response->assertSee('https://cdn.example.com/logo.png');
        $response->assertSee('width="24"', false);
        $response->assertSee('height="24"', false);
    }
}
