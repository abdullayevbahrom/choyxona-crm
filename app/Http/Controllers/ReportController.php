<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Room;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $this->validatedFilters($request);
        $reportData = $this->buildReportData($validated);

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

    public function exportCsv(Request $request): StreamedResponse
    {
        $validated = $this->validatedFilters($request);
        $reportData = $this->buildReportData($validated);

        $filename = "reports-" . now()->format("Ymd-His") . ".csv";

        return response()->streamDownload(
            function () use ($reportData): void {
                $handle = fopen("php://output", "wb");

                if ($handle === false) {
                    return;
                }

                fputcsv($handle, ["Bo'lim", "Nom", "Qiymat"]);
                fputcsv($handle, [
                    "Umumiy",
                    "Jami daromad",
                    (float) $reportData["totalRevenue"],
                ]);
                fputcsv($handle, [
                    "Umumiy",
                    "Yopilgan buyurtmalar",
                    (int) $reportData["ordersCount"],
                ]);

                fputcsv($handle, []);
                fputcsv($handle, ["Kunlik daromad", "Sana", "Daromad"]);
                foreach ($reportData["dailyRevenue"] as $row) {
                    fputcsv($handle, [
                        "Kunlik daromad",
                        $row->day,
                        (float) $row->revenue,
                    ]);
                }

                fputcsv($handle, []);
                fputcsv($handle, ["Oylik daromad", "Oy", "Daromad"]);
                foreach ($reportData["monthlyRevenue"] as $row) {
                    fputcsv($handle, [
                        "Oylik daromad",
                        $row->ym,
                        (float) $row->revenue,
                    ]);
                }

                fputcsv($handle, []);
                fputcsv($handle, ["TOP mahsulot", "Nomi", "Soni", "Daromad"]);
                foreach ($reportData["topItems"] as $row) {
                    fputcsv($handle, [
                        "TOP mahsulot",
                        $row->item_name,
                        (int) $row->total_qty,
                        (float) $row->revenue,
                    ]);
                }

                fputcsv($handle, []);
                fputcsv($handle, ["Xonalar", "Xona", "Buyurtma", "Daromad"]);
                foreach ($reportData["roomStats"] as $row) {
                    fputcsv($handle, [
                        "Xonalar",
                        $row->room_number,
                        (int) $row->orders_count,
                        (float) $row->revenue,
                    ]);
                }

                fputcsv($handle, []);
                fputcsv($handle, ["Kassirlar", "Kassir", "Chek", "Daromad"]);
                foreach ($reportData["cashierStats"] as $row) {
                    fputcsv($handle, [
                        "Kassirlar",
                        $row->cashier_name,
                        (int) $row->bills_count,
                        (float) $row->revenue,
                    ]);
                }

                fclose($handle);
            },
            $filename,
            [
                "Content-Type" => "text/csv; charset=UTF-8",
            ],
        );
    }

    public function exportXls(Request $request): Response
    {
        $validated = $this->validatedFilters($request);
        $reportData = $this->buildReportData($validated);

        return Excel::download(
            new ReportExport($validated, $reportData),
            "reports-" . now()->format("Ymd-His") . ".xlsx",
        );
    }

    public function exportPdf(Request $request): Response
    {
        $validated = $this->validatedFilters($request);
        $reportData = $this->buildReportData($validated);

        $pdf = Pdf::loadView("reports.pdf", [
            "filters" => $validated,
            "reportData" => $reportData,
        ])->setPaper("a4");

        return $pdf->download("reports-" . now()->format("Ymd-His") . ".pdf");
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            "date_from" => ["nullable", "date"],
            "date_to" => ["nullable", "date"],
            "room_id" => ["nullable", "integer", "exists:rooms,id"],
            "cashier_id" => ["nullable", "integer", "exists:users,id"],
        ]);
    }

    private function buildReportData(array $validated): array
    {
        $driver = DB::connection()->getDriverName();
        $monthExpr = match ($driver) {
            "mysql" => "date_format(orders.closed_at, '%Y-%m')",
            "pgsql" => "to_char(orders.closed_at, 'YYYY-MM')",
            default => "strftime('%Y-%m', orders.closed_at)",
        };

        $cacheTtlSeconds = (int) config("performance.report_cache_seconds", 30);
        $cacheKey =
            "reports:v2:" .
            md5(
                json_encode(
                    [$driver, $monthExpr, $validated],
                    JSON_THROW_ON_ERROR,
                ),
            );

        return Cache::remember(
            $cacheKey,
            now()->addSeconds($cacheTtlSeconds),
            function () use ($validated, $monthExpr) {
                $roomId = !empty($validated["room_id"])
                    ? (int) $validated["room_id"]
                    : null;
                $cashierId = !empty($validated["cashier_id"])
                    ? (int) $validated["cashier_id"]
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

                    if ($summaryRows->isNotEmpty()) {
                        $totalRevenue = (float) $summaryRows->sum(
                            "total_revenue",
                        );
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
                                    "revenue" => (float) $rows->sum(
                                        "total_revenue",
                                    ),
                                ],
                            )
                            ->sortByDesc("ym")
                            ->take(12)
                            ->values();
                    } else {
                        // Safety fallback: if summaries are not ready yet, use base tables.
                        $baseBills = DB::table("bills")
                            ->join("orders", "orders.id", "=", "bills.order_id")
                            ->where("orders.status", "closed");

                        $this->applyFilters($baseBills, $validated);

                        $totalRevenue = (float) (clone $baseBills)->sum(
                            "bills.total_amount",
                        );
                        $ordersCount = (int) (clone $baseBills)->count(
                            "orders.id",
                        );

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
