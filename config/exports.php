<?php

return [
    'report_retention_days' => (int) env('REPORT_EXPORT_RETENTION_DAYS', 30),
    'activity_retention_days' => (int) env('ACTIVITY_EXPORT_RETENTION_DAYS', 30),
];
