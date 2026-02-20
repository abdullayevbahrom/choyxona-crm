<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            "date_from" => ["nullable", "date"],
            "date_to" => ["nullable", "date"],
            "room_id" => ["nullable", "integer", "exists:rooms,id"],
            "cashier_id" => ["nullable", "integer", "exists:users,id"],
        ]);

        $driver = DB::connection()->getDriverName();
        $monthExpr = match ($driver) {
            "mysql" => "date_format(orders.closed_at, '%Y-%m')",
            "pgsql" => "to_char(orders.closed_at, 'YYYY-MM')",
            default => "strftime('%Y-%m', orders.closed_at)",
        };

        $cacheTtlSeconds = (int) config("performance.report_cache_seconds", 30);
        $cacheKey =
            "reports:v1:" .
            md5(
                json_encode(
                    [$driver, $monthExpr, $validated],
                    JSON_THROW_ON_ERROR,
                ),
            );

        $reportData = Cache::remember(
            $cacheKey,
            now()->addSeconds($cacheTtlSeconds),
            function () use ($validated, $monthExpr) {
                $useSummary =
                    empty($validated["room_id"]) &&
                    empty($validated["cashier_id"]);

                if ($useSummary) {
                    $summaryBase = DB::table("report_daily_summaries")->select([
                        "day",
                        "orders_count",
                        "total_revenue",
                    ]);

                    if (!empty($validated["date_from"])) {
                        $summaryBase->whereDate(
                            "day",
                            ">=",
                            $validated["date_from"],
                        );
                    }

                    if (!empty($validated["date_to"])) {
                        $summaryBase->whereDate(
                            "day",
                            "<=",
                            $validated["date_to"],
                        );
                    }

                    /** @var Collection<int, object> $summaryRows */
                    $summaryRows = $summaryBase->orderByDesc("day")->get();

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
                        ->groupBy(fn($row) => substr((string) $row->day, 0, 7))
                        ->map(
                            fn(Collection $rows, string $ym) => (object) [
                                "ym" => $ym,
                                "revenue" => (float) $rows->sum(
                                    "total_revenue",
                                ),
                            ],
                        )
                        ->sortByDesc("ym")
                        ->take(12)
                        ->values();
                } else {
                    $baseBills = DB::table("bills")
                        ->join("orders", "orders.id", "=", "bills.order_id")
                        ->where("orders.status", "closed");

                    $this->applyFilters($baseBills, $validated);

                    $totalRevenue = (float) (clone $baseBills)->sum(
                        "bills.total_amount",
                    );
                    $ordersCount = (int) (clone $baseBills)->count("orders.id");

                    $dailyRevenue = (clone $baseBills)
                        ->selectRaw(
                            "date(orders.closed_at) as day, sum(bills.total_amount) as revenue",
                        )
                        ->groupBy("day")
                        ->orderByDesc("day")
                        ->limit(31)
                        ->get();

                    $monthlyRevenue = (clone $baseBills)
                        ->selectRaw(
                            "{$monthExpr} as ym, sum(bills.total_amount) as revenue",
                        )
                        ->groupBy("ym")
                        ->orderByDesc("ym")
                        ->limit(12)
                        ->get();
                }

                $topItems = DB::table("order_items")
                    ->join("orders", "orders.id", "=", "order_items.order_id")
                    ->join(
                        "menu_items",
                        "menu_items.id",
                        "=",
                        "order_items.menu_item_id",
                    )
                    ->where("orders.status", "closed");

                $this->applyFilters($topItems, $validated);

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

                $this->applyFilters($roomStats, $validated);

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

                $this->applyFilters($cashierStats, $validated);

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

        return view("reports.index", [
            "filters" => $validated,
            "rooms" => Room::query()
                ->orderBy("number")
                ->get(["id", "number"]),
            "cashiers" => User::query()
                ->whereIn("role", ["cashier", "manager", "admin"])
                ->orderBy("name")
                ->get(["id", "name"]),
            "totalRevenue" => $reportData["totalRevenue"],
            "ordersCount" => $reportData["ordersCount"],
            "dailyRevenue" => $reportData["dailyRevenue"],
            "monthlyRevenue" => $reportData["monthlyRevenue"],
            "topItems" => $reportData["topItems"],
            "roomStats" => $reportData["roomStats"],
            "cashierStats" => $reportData["cashierStats"],
        ]);
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
                fn(Builder $q) => $q->where(
                    "orders.room_id",
                    $filters["room_id"],
                ),
            )
            ->when(
                !empty($filters["cashier_id"]),
                fn(Builder $q) => $q->where(
                    "orders.user_id",
                    $filters["cashier_id"],
                ),
            );
    }
}
