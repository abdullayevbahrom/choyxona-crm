<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportService
{
    public function getReportData(array $filters): array
    {
        $monthExpr = $this->monthExpression();
        $driver = DB::connection()->getDriverName();

        $cacheTtlSeconds = (int) config("performance.report_cache_seconds", 30);
        $cacheKey =
            "reports:v3:" .
            md5(
                json_encode([$driver, $monthExpr, $filters], JSON_THROW_ON_ERROR),
            );

        return Cache::remember(
            $cacheKey,
            now()->addSeconds($cacheTtlSeconds),
            function () use ($filters, $monthExpr) {
                $roomId = !empty($filters["room_id"])
                    ? (int) $filters["room_id"]
                    : null;
                $cashierId = !empty($filters["cashier_id"])
                    ? (int) $filters["cashier_id"]
                    : null;
                $summaryMode = match (true) {
                    $roomId === null && $cashierId === null => "global",
                    $roomId !== null && $cashierId === null => "room",
                    $roomId === null && $cashierId !== null => "cashier",
                    default => "none",
                };

                if ($summaryMode !== "none") {
                    $summaryBase = match ($summaryMode) {
                        "room" => DB::table("report_daily_room_summaries")
                            ->where("room_id", $roomId)
                            ->select(["day", "orders_count", "total_revenue"]),
                        "cashier" => DB::table("report_daily_cashier_summaries")
                            ->where("cashier_id", $cashierId)
                            ->select(["day", "orders_count", "total_revenue"]),
                        default => DB::table("report_daily_summaries")->select([
                            "day",
                            "orders_count",
                            "total_revenue",
                        ]),
                    };

                    if (!empty($filters["date_from"])) {
                        $summaryBase->whereDate("day", ">=", $filters["date_from"]);
                    }

                    if (!empty($filters["date_to"])) {
                        $summaryBase->whereDate("day", "<=", $filters["date_to"]);
                    }

                    /** @var Collection<int, object> $summaryRows */
                    $summaryRows = $summaryBase->orderByDesc("day")->get();

                    if ($summaryRows->isNotEmpty()) {
                        $totalRevenue = (float) $summaryRows->sum("total_revenue");
                        $ordersCount = (int) $summaryRows->sum("orders_count");

                        $dailyRevenue = $summaryRows
                            ->take(31)
                            ->map(
                                fn($row) => (object) [
                                    "day" => $row->day,
                                    "revenue" => (float) $row->total_revenue,
                                ],
                            )
                            ->values();

                        $monthlyRevenue = $summaryRows
                            ->groupBy(
                                fn($row) => Str::of((string) $row->day)
                                    ->substr(0, 7)
                                    ->toString(),
                            )
                            ->map(
                                fn(Collection $rows, string $ym) => (object) [
                                    "ym" => $ym,
                                    "revenue" => (float) $rows->sum("total_revenue"),
                                ],
                            )
                            ->sortByDesc("ym")
                            ->take(12)
                            ->values();
                    } else {
                        [$totalRevenue, $ordersCount, $dailyRevenue, $monthlyRevenue] = $this->buildTotalsAndRevenueFromBase(
                            $filters,
                            $monthExpr,
                        );
                    }
                } else {
                    [$totalRevenue, $ordersCount, $dailyRevenue, $monthlyRevenue] = $this->buildTotalsAndRevenueFromBase(
                        $filters,
                        $monthExpr,
                    );
                }

                $topItems = DB::table("order_items")
                    ->join("orders", "orders.id", "=", "order_items.order_id")
                    ->join("menu_items", "menu_items.id", "=", "order_items.menu_item_id")
                    ->where("orders.status", "closed");

                $this->applyFilters($topItems, $filters);

                $topItems = $topItems
                    ->selectRaw(
                        "menu_items.name as item_name, sum(order_items.quantity) as total_qty, sum(order_items.subtotal) as revenue",
                    )
                    ->groupBy("menu_items.id", "menu_items.name")
                    ->orderByDesc("total_qty")
                    ->limit(10)
                    ->get();

                $roomStats = DB::table("bills")
                    ->join("orders", "orders.id", "=", "bills.order_id")
                    ->join("rooms", "rooms.id", "=", "orders.room_id")
                    ->where("orders.status", "closed");

                $this->applyFilters($roomStats, $filters);

                $roomStats = $roomStats
                    ->selectRaw(
                        "rooms.number as room_number, count(orders.id) as orders_count, sum(bills.total_amount) as revenue",
                    )
                    ->groupBy("rooms.id", "rooms.number")
                    ->orderByDesc("orders_count")
                    ->limit(20)
                    ->get();

                $cashierStats = DB::table("bills")
                    ->join("orders", "orders.id", "=", "bills.order_id")
                    ->leftJoin("users", "users.id", "=", "orders.user_id")
                    ->where("orders.status", "closed");

                $this->applyFilters($cashierStats, $filters);

                $cashierStats = $cashierStats
                    ->selectRaw(
                        "coalesce(users.name, 'Nomalum') as cashier_name, count(orders.id) as bills_count, sum(bills.total_amount) as revenue",
                    )
                    ->groupBy("users.id", "users.name")
                    ->orderByDesc("bills_count")
                    ->limit(20)
                    ->get();

                return [
                    "totalRevenue" => $totalRevenue,
                    "ordersCount" => $ordersCount,
                    "dailyRevenue" => $dailyRevenue,
                    "monthlyRevenue" => $monthlyRevenue,
                    "topItems" => $topItems,
                    "roomStats" => $roomStats,
                    "cashierStats" => $cashierStats,
                ];
            },
        );
    }

    public function streamRows(array $filters): \Generator
    {
        $monthExpr = $this->monthExpression();
        $baseBills = $this->baseBillsQuery($filters);

        $totalRevenue = (float) (clone $baseBills)->sum("bills.total_amount");
        $ordersCount = (int) (clone $baseBills)->count("orders.id");

        yield ["Bo'lim", "Nom", "Qiymat", "Qiymat 2"];
        yield ["Umumiy", "Jami daromad", $totalRevenue, null];
        yield ["Umumiy", "Yopilgan buyurtmalar", $ordersCount, null];
        yield [null, null, null, null];

        yield ["Kunlik daromad", "Sana", "Daromad", null];
        $dailyQuery = (clone $baseBills)
            ->selectRaw("date(orders.closed_at) as day, sum(bills.total_amount) as revenue")
            ->groupBy("day")
            ->orderByDesc("day");
        foreach ($dailyQuery->cursor() as $row) {
            yield ["Kunlik daromad", $row->day, (float) $row->revenue, null];
        }
        yield [null, null, null, null];

        yield ["Oylik daromad", "Oy", "Daromad", null];
        $monthlyQuery = (clone $baseBills)
            ->selectRaw("{$monthExpr} as ym, sum(bills.total_amount) as revenue")
            ->groupBy("ym")
            ->orderByDesc("ym");
        foreach ($monthlyQuery->cursor() as $row) {
            yield ["Oylik daromad", $row->ym, (float) $row->revenue, null];
        }
        yield [null, null, null, null];

        yield ["TOP mahsulot", "Nomi", "Soni", "Daromad"];
        $topItems = DB::table("order_items")
            ->join("orders", "orders.id", "=", "order_items.order_id")
            ->join("menu_items", "menu_items.id", "=", "order_items.menu_item_id")
            ->where("orders.status", "closed");
        $this->applyFilters($topItems, $filters);
        $topItems = $topItems
            ->selectRaw(
                "menu_items.name as item_name, sum(order_items.quantity) as total_qty, sum(order_items.subtotal) as revenue",
            )
            ->groupBy("menu_items.id", "menu_items.name")
            ->orderByDesc("total_qty")
            ->limit(10);

        foreach ($topItems->cursor() as $row) {
            yield [
                "TOP mahsulot",
                $row->item_name,
                (int) $row->total_qty,
                (float) $row->revenue,
            ];
        }
        yield [null, null, null, null];

        yield ["Xonalar", "Xona", "Buyurtma", "Daromad"];
        $roomStats = DB::table("bills")
            ->join("orders", "orders.id", "=", "bills.order_id")
            ->join("rooms", "rooms.id", "=", "orders.room_id")
            ->where("orders.status", "closed");
        $this->applyFilters($roomStats, $filters);
        $roomStats = $roomStats
            ->selectRaw(
                "rooms.number as room_number, count(orders.id) as orders_count, sum(bills.total_amount) as revenue",
            )
            ->groupBy("rooms.id", "rooms.number")
            ->orderByDesc("orders_count")
            ->limit(20);

        foreach ($roomStats->cursor() as $row) {
            yield [
                "Xonalar",
                $row->room_number,
                (int) $row->orders_count,
                (float) $row->revenue,
            ];
        }
        yield [null, null, null, null];

        yield ["Kassirlar", "Kassir", "Chek", "Daromad"];
        $cashierStats = DB::table("bills")
            ->join("orders", "orders.id", "=", "bills.order_id")
            ->leftJoin("users", "users.id", "=", "orders.user_id")
            ->where("orders.status", "closed");
        $this->applyFilters($cashierStats, $filters);
        $cashierStats = $cashierStats
            ->selectRaw(
                "coalesce(users.name, 'Nomalum') as cashier_name, count(orders.id) as bills_count, sum(bills.total_amount) as revenue",
            )
            ->groupBy("users.id", "users.name")
            ->orderByDesc("bills_count")
            ->limit(20);

        foreach ($cashierStats->cursor() as $row) {
            yield [
                "Kassirlar",
                $row->cashier_name,
                (int) $row->bills_count,
                (float) $row->revenue,
            ];
        }
    }

    public function streamCsv(array $filters, $handle): void
    {
        foreach ($this->streamRows($filters) as $row) {
            fputcsv($handle, $row);
        }
    }

    private function monthExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            "mysql" => "date_format(orders.closed_at, '%Y-%m')",
            "pgsql" => "to_char(orders.closed_at, 'YYYY-MM')",
            default => "strftime('%Y-%m', orders.closed_at)",
        };
    }

    private function buildTotalsAndRevenueFromBase(
        array $filters,
        string $monthExpr,
    ): array {
        $baseBills = $this->baseBillsQuery($filters);

        $totalRevenue = (float) (clone $baseBills)->sum("bills.total_amount");
        $ordersCount = (int) (clone $baseBills)->count("orders.id");

        $dailyRevenue = (clone $baseBills)
            ->selectRaw("date(orders.closed_at) as day, sum(bills.total_amount) as revenue")
            ->groupBy("day")
            ->orderByDesc("day")
            ->limit(31)
            ->get();

        $monthlyRevenue = (clone $baseBills)
            ->selectRaw("{$monthExpr} as ym, sum(bills.total_amount) as revenue")
            ->groupBy("ym")
            ->orderByDesc("ym")
            ->limit(12)
            ->get();

        return [$totalRevenue, $ordersCount, $dailyRevenue, $monthlyRevenue];
    }

    private function baseBillsQuery(array $filters): Builder
    {
        $query = DB::table("bills")
            ->join("orders", "orders.id", "=", "bills.order_id")
            ->where("orders.status", "closed");

        $this->applyFilters($query, $filters);

        return $query;
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when(
                !empty($filters["date_from"]),
                fn(Builder $q) => $q->whereDate(
                    "orders.closed_at",
                    ">=",
                    $filters["date_from"],
                ),
            )
            ->when(
                !empty($filters["date_to"]),
                fn(Builder $q) => $q->whereDate(
                    "orders.closed_at",
                    "<=",
                    $filters["date_to"],
                ),
            )
            ->when(
                !empty($filters["room_id"]),
                fn(Builder $q) => $q->where("orders.room_id", $filters["room_id"]),
            )
            ->when(
                !empty($filters["cashier_id"]),
                fn(Builder $q) => $q->where("orders.user_id", $filters["cashier_id"]),
            );
    }
}
