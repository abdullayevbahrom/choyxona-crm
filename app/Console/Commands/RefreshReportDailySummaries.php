<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshReportDailySummaries extends Command
{
    protected $signature = "reports:refresh-daily-summaries {--days=365 : Refresh summaries for last N days}";

    protected $description = "Refresh pre-aggregated daily revenue summaries for report performance";

    public function handle(): int
    {
        $days = max(1, (int) $this->option("days"));
        $fromDate = now()
            ->subDays($days - 1)
            ->toDateString();
        $fromDateTime = $fromDate . " 00:00:00";

        $rows = DB::table("bills")
            ->join("orders", "orders.id", "=", "bills.order_id")
            ->where("orders.status", "closed")
            ->where("orders.closed_at", ">=", $fromDateTime)
            ->selectRaw(
                "date(orders.closed_at) as day, count(orders.id) as orders_count, sum(bills.total_amount) as total_revenue",
            )
            ->groupByRaw("date(orders.closed_at)")
            ->get()
            ->map(
                fn($row) => [
                    "day" => $row->day,
                    "orders_count" => (int) $row->orders_count,
                    "total_revenue" => (float) $row->total_revenue,
                    "created_at" => now(),
                    "updated_at" => now(),
                ],
            )
            ->all();

        $roomRows = DB::table("bills")
            ->join("orders", "orders.id", "=", "bills.order_id")
            ->where("orders.status", "closed")
            ->where("orders.closed_at", ">=", $fromDateTime)
            ->selectRaw(
                "date(orders.closed_at) as day, orders.room_id as room_id, count(orders.id) as orders_count, sum(bills.total_amount) as total_revenue",
            )
            ->groupByRaw("date(orders.closed_at), orders.room_id")
            ->get()
            ->map(
                fn($row) => [
                    "day" => $row->day,
                    "room_id" => (int) $row->room_id,
                    "orders_count" => (int) $row->orders_count,
                    "total_revenue" => (float) $row->total_revenue,
                    "created_at" => now(),
                    "updated_at" => now(),
                ],
            )
            ->all();

        $cashierRows = DB::table("bills")
            ->join("orders", "orders.id", "=", "bills.order_id")
            ->where("orders.status", "closed")
            ->where("orders.closed_at", ">=", $fromDateTime)
            ->whereNotNull("orders.user_id")
            ->selectRaw(
                "date(orders.closed_at) as day, orders.user_id as cashier_id, count(orders.id) as orders_count, sum(bills.total_amount) as total_revenue",
            )
            ->groupByRaw("date(orders.closed_at), orders.user_id")
            ->get()
            ->map(
                fn($row) => [
                    "day" => $row->day,
                    "cashier_id" => (int) $row->cashier_id,
                    "orders_count" => (int) $row->orders_count,
                    "total_revenue" => (float) $row->total_revenue,
                    "created_at" => now(),
                    "updated_at" => now(),
                ],
            )
            ->all();

        DB::transaction(function () use (
            $fromDate,
            $rows,
            $roomRows,
            $cashierRows,
        ) {
            DB::table("report_daily_summaries")
                ->where("day", ">=", $fromDate)
                ->delete();

            DB::table("report_daily_room_summaries")
                ->where("day", ">=", $fromDate)
                ->delete();

            DB::table("report_daily_cashier_summaries")
                ->where("day", ">=", $fromDate)
                ->delete();

            if ($rows !== []) {
                DB::table("report_daily_summaries")->insert($rows);
            }

            if ($roomRows !== []) {
                DB::table("report_daily_room_summaries")->insert($roomRows);
            }

            if ($cashierRows !== []) {
                DB::table("report_daily_cashier_summaries")->insert(
                    $cashierRows,
                );
            }
        });

        $this->info(
            "Daily summaries refreshed from {$fromDate}. Rows: " .
                count($rows) .
                ", room rows: " .
                count($roomRows) .
                ", cashier rows: " .
                count($cashierRows),
        );

        return self::SUCCESS;
    }
}
