<?php

return [
    'default_per_page' => (int) env('PAGINATION_PER_PAGE', 10),
    'allowed_per_page' => [10, 25, 50, 100],
];
