<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use App\Models\User;
use App\Services\BillService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoDataSeeder extends Seeder
{
    private const DEFAULT_ROOMS_COUNT = 28;

    private const DEFAULT_EXTRA_MENU_ITEMS_PER_TYPE = 8;

    private const DEFAULT_CLOSED_ORDERS_COUNT = 260;

    private const DEFAULT_CANCELLED_ORDERS_COUNT = 40;

    private const DEFAULT_OPEN_ORDERS_COUNT = 12;

    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
        $this->truncateOperationalData();

        $orderService = app(OrderService::class);
        $billService = app(BillService::class);

        $roomsCount = $this->intEnv(
            'DEMO_SEED_ROOMS_COUNT',
            self::DEFAULT_ROOMS_COUNT,
            1,
            300,
        );
        $extraMenuPerType = $this->intEnv(
            'DEMO_SEED_MENU_EXTRA_PER_TYPE',
            self::DEFAULT_EXTRA_MENU_ITEMS_PER_TYPE,
            0,
            100,
        );
        $closedOrdersCount = $this->intEnv(
            'DEMO_SEED_CLOSED_ORDERS',
            self::DEFAULT_CLOSED_ORDERS_COUNT,
            0,
            20000,
        );
        $cancelledOrdersCount = $this->intEnv(
            'DEMO_SEED_CANCELLED_ORDERS',
            self::DEFAULT_CANCELLED_ORDERS_COUNT,
            0,
            20000,
        );
        $openOrdersCount = $this->intEnv(
            'DEMO_SEED_OPEN_ORDERS',
            self::DEFAULT_OPEN_ORDERS_COUNT,
            1,
            $roomsCount,
        );

        $rooms = $this->seedRooms($roomsCount);
        $menuItems = $this->seedMenuItems($extraMenuPerType);

        $cashierIds = User::query()
            ->whereIn('role', [
                User::ROLE_CASHIER,
                User::ROLE_MANAGER,
                User::ROLE_ADMIN,
            ])
            ->pluck('id')
            ->all();

        $pricedMenuItems = $menuItems
            ->whereNotNull('price')
            ->where('is_active', true)
            ->values();

        for ($i = 0; $i < $closedOrdersCount; $i++) {
            $room = $rooms->random();
            $openedAt = $this->randomDateTime('-45 days', '-1 day');
            $closedAt = $this->randomDateTime(
                $openedAt->copy()->addMinutes(20),
                'now',
            );

            $order = Order::query()->create([
                'room_id' => $room->id,
                'order_number' => $orderService->nextOrderNumber(),
                'status' => Order::STATUS_CLOSED,
                'total_amount' => 0,
                'notes' => $this->randomNote(25),
                'opened_at' => $openedAt,
                'closed_at' => $closedAt,
                'user_id' => Arr::random($cashierIds),
                'created_at' => $openedAt,
                'updated_at' => $closedAt,
            ]);

            $this->attachRandomItems(
                $order,
                $pricedMenuItems,
                random_int(2, 8),
            );
            $orderService->recalculateTotal($order);
            $order->refresh();

            [$discountPercent, $discountAmount] = $this->randomDiscount(
                (float) $order->total_amount,
            );

            Bill::query()->create([
                'order_id' => $order->id,
                'room_id' => $room->id,
                'bill_number' => $billService->nextBillNumber(),
                'subtotal' => $order->total_amount,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'total_amount' => max(
                    0,
                    (float) $order->total_amount -
                        (float) ($discountAmount ?? 0),
                ),
                'payment_method' => Arr::random([
                    Bill::PAYMENT_CASH,
                    Bill::PAYMENT_CARD,
                    Bill::PAYMENT_TRANSFER,
                ]),
                'is_printed' => true,
                'printed_at' => $closedAt,
                'created_at' => $closedAt,
                'updated_at' => $closedAt,
            ]);
        }

        for ($i = 0; $i < $cancelledOrdersCount; $i++) {
            $room = $rooms->random();
            $openedAt = $this->randomDateTime('-30 days', '-1 day');
            $closedAt = $this->randomDateTime(
                $openedAt->copy()->addMinutes(10),
                'now',
            );

            Order::query()->create([
                'room_id' => $room->id,
                'order_number' => $orderService->nextOrderNumber(),
                'status' => Order::STATUS_CANCELLED,
                'total_amount' => 0,
                'notes' => $this->randomSentence(5),
                'opened_at' => $openedAt,
                'closed_at' => $closedAt,
                'user_id' => Arr::random($cashierIds),
                'created_at' => $openedAt,
                'updated_at' => $closedAt,
            ]);
        }

        $openRooms = $rooms
            ->shuffle()
            ->take(min($openOrdersCount, $rooms->count()));
        foreach ($openRooms as $room) {
            $order = $orderService->createOrder(
                room: $room,
                userId: Arr::random($cashierIds),
                notes: $this->randomNote(30),
            );

            $this->attachRandomItems(
                $order,
                $pricedMenuItems,
                random_int(1, 5),
            );
            $orderService->recalculateTotal($order);
        }

        DB::statement(
            'update rooms set status = ? where id not in (select room_id from orders where status = ?)',
            [Room::STATUS_EMPTY, Order::STATUS_OPEN],
        );

        DB::statement(
            'update rooms set status = ? where id in (select room_id from orders where status = ?)',
            [Room::STATUS_OCCUPIED, Order::STATUS_OPEN],
        );

        $this->command?->info('Demo dataset yaratildi.');
        $this->command?->info(
            "Rooms: {$rooms->count()}, Menu items: {$menuItems->count()}",
        );
        $this->command?->info(
            "Orders => closed: {$closedOrdersCount}, cancelled: {$cancelledOrdersCount}, open: {$openRooms->count()}",
        );
    }

    private function truncateOperationalData(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $this->truncateIfExists('bills');
        $this->truncateIfExists('order_items');
        $this->truncateIfExists('orders');
        $this->truncateIfExists('menu_items');
        $this->truncateIfExists('rooms');
        $this->truncateIfExists('number_sequences');
        $this->truncateIfExists('report_daily_summaries');
        $this->truncateIfExists('report_daily_room_summaries');
        $this->truncateIfExists('report_daily_cashier_summaries');

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function truncateIfExists(string $table): void
    {
        if (Schema::hasTable($table)) {
            DB::table($table)->truncate();
        }
    }

    private function seedRooms(int $roomsCount): Collection
    {
        $rooms = collect();
        $baseNumber = 100;

        for ($i = 1; $i <= $roomsCount; $i++) {
            $number = (string) ($baseNumber + $i);
            $rooms->push(
                Room::query()->create([
                    'number' => $number,
                    'name' => random_int(1, 100) <= 60 ? "Xona {$number}" : null,
                    'capacity' => Arr::random([2, 4, 6, 8, 10]),
                    'status' => Room::STATUS_EMPTY,
                    'is_active' => true,
                    'description' => random_int(1, 100) <= 25
                            ? $this->randomSentence(5)
                            : null,
                ]),
            );
        }

        return $rooms;
    }

    private function seedMenuItems(int $extraItemsPerType): Collection
    {
        $catalog = [
            MenuItem::TYPE_FOOD => [
                'Palov',
                'Lagmon',
                'Shurva',
                'Kabob',
                'Manti',
                'Qozon Kabob',
                'Chuchvara',
                'Dimlama',
            ],
            MenuItem::TYPE_DRINK => [
                'Ko\'k choy',
                'Qora choy',
                'Qahva',
                'Sharbat',
                'Mineral suv',
                'Limonad',
            ],
            MenuItem::TYPE_BREAD => ['Non', 'Patir', 'Kulcha', 'Lochira'],
            MenuItem::TYPE_SALAD => [
                'Achichuk',
                'Toshkent salati',
                'Karam salat',
                'Bahor salati',
            ],
            MenuItem::TYPE_SAUCE => [
                'Pomidor sousi',
                'Sarimsoq sousi',
                'Qatiq sousi',
                'Achchiq sous',
            ],
        ];

        $items = collect();

        foreach ($catalog as $type => $names) {
            foreach ($names as $name) {
                $items->push(
                    MenuItem::query()->create([
                        'name' => $name,
                        'type' => $type,
                        'price' => random_int(1, 100) <= 8
                                ? null
                                : random_int(8000, 120000),
                        'stock_quantity' => random_int(1, 100) <= 70
                                ? random_int(5, 120)
                                : null,
                        'unit' => Arr::random([
                            'dona',
                            'porsiya',
                            'litr',
                            'stakan',
                        ]),
                        'description' => random_int(1, 100) <= 20
                                ? $this->randomSentence(4)
                                : null,
                        'is_active' => random_int(1, 100) <= 92,
                    ]),
                );
            }

            for ($i = 1; $i <= $extraItemsPerType; $i++) {
                $items->push(
                    MenuItem::query()->create([
                        'name' => $this->randomMenuName($type, $i),
                        'type' => $type,
                        'price' => random_int(1, 100) <= 10
                                ? null
                                : random_int(6000, 150000),
                        'stock_quantity' => random_int(1, 100) <= 65
                                ? random_int(3, 150)
                                : null,
                        'unit' => Arr::random([
                            'dona',
                            'porsiya',
                            'litr',
                            'gramm',
                        ]),
                        'description' => random_int(1, 100) <= 30
                                ? $this->randomSentence(5)
                                : null,
                        'is_active' => random_int(1, 100) <= 90,
                    ]),
                );
            }
        }

        return $items;
    }

    private function attachRandomItems(
        Order $order,
        Collection $menuItems,
        int $count,
    ): void {
        $selected = $menuItems->shuffle()->take($count);

        foreach ($selected as $menuItem) {
            $qty = random_int(1, 4);
            $unitPrice = (float) $menuItem->price;

            OrderItem::query()->create([
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $qty * $unitPrice,
                'notes' => random_int(1, 100) <= 15 ? $this->randomSentence(3) : null,
                'created_at' => $order->opened_at,
                'updated_at' => $order->opened_at,
            ]);
        }
    }

    private function randomDiscount(float $subtotal): array
    {
        if ($subtotal <= 0 || random_int(1, 100) > 35) {
            return [null, null];
        }

        $percent = Arr::random([5, 7.5, 10, 12.5, 15]);
        $amount = round($subtotal * ($percent / 100), 2);

        return [$percent, $amount];
    }

    private function randomDateTime(
        Carbon|string $from,
        Carbon|string $to,
    ): Carbon {
        $start = $from instanceof Carbon ? $from : Carbon::parse($from);
        $end = $to instanceof Carbon ? $to : Carbon::parse($to);

        $startTs = $start->getTimestamp();
        $endTs = max($startTs + 60, $end->getTimestamp());

        return Carbon::createFromTimestamp(random_int($startTs, $endTs));
    }

    private function randomNote(int $chancePercent): ?string
    {
        if (random_int(1, 100) > $chancePercent) {
            return null;
        }

        return $this->randomSentence(random_int(3, 6));
    }

    private function randomSentence(int $wordCount): string
    {
        $pool = [
            'issiq',
            'tez',
            'kam',
            'ko\'p',
            'achchiq',
            'shirin',
            'limon',
            'sous',
            'non',
            'taom',
            'choy',
            'mijoz',
            'xona',
            'buyurtma',
            'kassir',
            'yangilash',
            'tayyor',
            'kutmoqda',
            'sifatli',
            'yangi',
        ];

        $words = [];
        for ($i = 0; $i < $wordCount; $i++) {
            $words[] = Arr::random($pool);
        }

        return ucfirst(implode(' ', $words)).'.';
    }

    private function randomMenuName(string $type, int $index): string
    {
        $prefix = match ($type) {
            MenuItem::TYPE_FOOD => 'Taom',
            MenuItem::TYPE_DRINK => 'Ichimlik',
            MenuItem::TYPE_BREAD => 'Non',
            MenuItem::TYPE_SALAD => 'Salat',
            MenuItem::TYPE_SAUCE => 'Sous',
            default => 'Mahsulot',
        };

        return "{$prefix} {$index}";
    }

    private function intEnv(string $key, int $default, int $min, int $max): int
    {
        $value = (int) env($key, $default);

        if ($value < $min) {
            return $min;
        }

        if ($value > $max) {
            return $max;
        }

        return $value;
    }
}
