<?php

return [
    'enabled' => (bool) env('OBS_ENABLED', true),
    'slow_request_ms' => (int) env('OBS_SLOW_REQUEST_MS', 700),
];
