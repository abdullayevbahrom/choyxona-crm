<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Order;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BillVerifyRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_verify_route_can_be_opened_without_auth(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $room = Room::query()->create([
            'number' => '777',
            'status' => Room::STATUS_EMPTY,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'room_id' => $room->id,
            'order_number' => 'ORD-2026-0777',
            'status' => Order::STATUS_CLOSED,
            'total_amount' => 45000,
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
            'user_id' => $cashier->id,
        ]);

        $bill = Bill::query()->create([
            'order_id' => $order->id,
            'room_id' => $room->id,
            'bill_number' => 'CHK-2026-0777',
            'subtotal' => 45000,
            'total_amount' => 45000,
            'is_printed' => true,
        ]);

        $url = URL::signedRoute('bills.verify', ['bill' => $bill->id]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Chek tasdiqlandi')
            ->assertSee($bill->bill_number);
    }

    public function test_unsigned_verify_route_is_rejected(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $room = Room::query()->create([
            'number' => '778',
            'status' => Room::STATUS_EMPTY,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'room_id' => $room->id,
            'order_number' => 'ORD-2026-0778',
            'status' => Order::STATUS_CLOSED,
            'total_amount' => 32000,
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
            'user_id' => $cashier->id,
        ]);

        $bill = Bill::query()->create([
            'order_id' => $order->id,
            'room_id' => $room->id,
            'bill_number' => 'CHK-2026-0778',
            'subtotal' => 32000,
            'total_amount' => 32000,
            'is_printed' => true,
        ]);

        $this->get(
            route('bills.verify', ['bill' => $bill->id], false),
        )->assertForbidden();
    }
}
