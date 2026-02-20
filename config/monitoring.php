<?php

return [
    "failed_jobs_threshold" => (int) env("MONITOR_FAILED_JOBS_THRESHOLD", 5),
    "queue_backlog_threshold" => (int) env(
        "MONITOR_QUEUE_BACKLOG_THRESHOLD",
        200,
    ),
    "summary_stale_hours" => (int) env("MONITOR_SUMMARY_STALE_HOURS", 2),
    "min_disk_free_percent" => (float) env("MONITOR_MIN_DISK_FREE_PERCENT", 5),
];
