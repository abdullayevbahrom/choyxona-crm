<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService
{
    public function __construct(
        private readonly NumberSequenceService $numberSequenceService,
    ) {}

    public function canCreateOrder(Room $room): bool
    {
        return $room->is_active &&
            ! Order::query()
                ->where('room_id', $room->id)
                ->where('status', Order::STATUS_OPEN)
                ->exists();
    }

    public function createOrder(
        Room $room,
        ?int $userId = null,
        ?string $notes = null,
    ): Order {
        if (! $this->canCreateOrder($room)) {
            throw new RuntimeException(
                'Bu xonada allaqachon ochiq buyurtma mavjud.',
            );
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return DB::transaction(function () use (
                    $room,
                    $userId,
                    $notes,
                ) {
                    $order = Order::query()->create([
                        'room_id' => $room->id,
                        'order_number' => $this->nextOrderNumber(),
                        'status' => Order::STATUS_OPEN,
                        'total_amount' => 0,
                        'notes' => $notes,
                        'opened_at' => now(),
                        'user_id' => $userId,
                    ]);

                    $room->update(['status' => Room::STATUS_OCCUPIED]);

                    return $order;
                });
            } catch (QueryException $e) {
                if (! $this->isDuplicateOrderNumberError($e) || $attempt === 4) {
                    throw $e;
                }
            }
        }

        throw new RuntimeException(
            'Buyurtma raqami yaratishda xatolik yuz berdi.',
        );
    }

    public function addItem(
        Order $order,
        MenuItem $menuItem,
        int $quantity = 1,
        ?string $notes = null,
    ): OrderItem {
        $this->ensureOrderIsOpen($order);

        if (! $menuItem->is_active) {
            throw new RuntimeException(
                'Nofaol mahsulotni buyurtmaga qo\'shib bo\'lmaydi.',
            );
        }

        $quantity = max(1, $quantity);
        $priceSnapshot = (float) ($menuItem->price ?? 0);

        return DB::transaction(function () use (
            $order,
            $menuItem,
            $quantity,
            $priceSnapshot,
            $notes,
        ) {
            $item = OrderItem::query()
                ->where('order_id', $order->id)
                ->where('menu_item_id', $menuItem->id)
                ->first();

            if ($item) {
                $item->quantity += $quantity;
                $item->unit_price = $priceSnapshot;
                $item->subtotal = $item->quantity * $item->unit_price;
                if ($notes !== null) {
                    $item->notes = $notes;
                }
                $item->save();
            } else {
                $item = OrderItem::query()->create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $quantity,
                    'unit_price' => $priceSnapshot,
                    'subtotal' => $priceSnapshot * $quantity,
                    'notes' => $notes,
                ]);
            }

            $this->recalculateTotal($order);

            return $item;
        });
    }

    public function updateItemQuantity(
        Order $order,
        OrderItem $item,
        int $quantity,
    ): void {
        $this->ensureOrderIsOpen($order);

        if ($item->order_id !== $order->id) {
            throw new RuntimeException('Mahsulot bu buyurtmaga tegishli emas.');
        }

        $quantity = max(1, $quantity);

        DB::transaction(function () use ($order, $item, $quantity) {
            $item->update([
                'quantity' => $quantity,
                'subtotal' => $quantity * (float) $item->unit_price,
            ]);

            $this->recalculateTotal($order);
        });
    }

    public function removeItem(Order $order, OrderItem $item): void
    {
        $this->ensureOrderIsOpen($order);

        if ($item->order_id !== $order->id) {
            throw new RuntimeException('Mahsulot bu buyurtmaga tegishli emas.');
        }

        DB::transaction(function () use ($order, $item) {
            $item->delete();
            $this->recalculateTotal($order);
        });
    }

    public function cancelOrder(Order $order): void
    {
        $this->ensureOrderIsOpen($order);

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'closed_at' => now(),
            ]);

            Room::query()
                ->whereKey($order->room_id)
                ->update([
                    'status' => Room::STATUS_EMPTY,
                ]);
        });
    }

    public function recalculateTotal(Order $order): void
    {
        $sum = (float) $order->items()->sum('subtotal');
        $order->update(['total_amount' => $sum]);
    }

    public function attachServingWaiter(Order $order, ?int $userId): void
    {
        if (! $userId) {
            return;
        }

        $isWaiter = User::query()
            ->whereKey($userId)
            ->where('role', User::ROLE_WAITER)
            ->exists();

        if (! $isWaiter) {
            return;
        }

        $order->waiters()->syncWithoutDetaching([$userId]);
    }

    public function nextOrderNumber(): string
    {
        $year = (int) now()->format('Y');
        $prefix = "ORD-{$year}-";
        $next = $this->numberSequenceService->next('ORD', $year);

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function ensureOrderIsOpen(Order $order): void
    {
        if ($order->status !== Order::STATUS_OPEN) {
            throw new RuntimeException(
                'Yopilgan yoki bekor qilingan buyurtmada bu amalni bajarib bo\'lmaydi.',
            );
        }
    }

    private function isDuplicateOrderNumberError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'orders.order_number') ||
            str_contains($e->getMessage(), 'orders_order_number_unique') ||
            str_contains(
                $e->getMessage(),
                'UNIQUE constraint failed: orders.order_number',
            );
    }
}
