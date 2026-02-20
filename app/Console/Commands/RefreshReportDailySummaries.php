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
        $fromDate = now()->subDays($days - 1)->toDateString();

        $rows = DB::table("bills")
            ->join("orders", "orders.id", "=", "bills.order_id")
            ->where("orders.status", "closed")
            ->whereDate("orders.closed_at", ">=", $fromDate)
            ->selectRaw(
                "date(orders.closed_at) as day, count(orders.id) as orders_count, sum(bills.total_amount) as total_revenue",
            )
            ->groupByRaw("date(orders.closed_at)")
            ->get()
            ->map(fn($row) => [
                "day" => $row->day,
                "orders_count" => (int) $row->orders_count,
                "total_revenue" => (float) $row->total_revenue,
                "created_at" => now(),
                "updated_at" => now(),
            ])
            ->all();

        DB::transaction(function () use ($fromDate, $rows) {
            DB::table("report_daily_summaries")
                ->where("day", ">=", $fromDate)
                ->delete();

            if ($rows !== []) {
                DB::table("report_daily_summaries")->insert($rows);
            }
        });

        $this->info(
            "Daily summaries refreshed from {$fromDate}. Rows: " . count($rows),
        );

        return self::SUCCESS;
    }
}
