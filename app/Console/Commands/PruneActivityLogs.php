<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class PruneActivityLogs extends Command
{
    protected $signature = 'activity-logs:prune {--days=90 : Keep logs newer than this many days}';

    protected $description = 'Delete old activity logs based on retention days';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('Days must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $deleted = ActivityLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} activity logs older than {$days} days.");

        return self::SUCCESS;
    }
}
