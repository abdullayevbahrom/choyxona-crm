<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromGenerator;

class ReportStreamExport implements FromGenerator
{
    public function __construct(private readonly array $filters) {}

    public function generator(): \Generator
    {
        /** @var ReportService $service */
        $service = app(ReportService::class);

        return $service->streamSafeRows($this->filters);
    }
}
