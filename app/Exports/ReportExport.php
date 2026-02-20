<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ReportExport implements FromView
{
    public function __construct(
        private readonly array $filters,
        private readonly array $reportData,
    ) {}

    public function view(): View
    {
        return view("reports.xls", [
            "filters" => $this->filters,
            "reportData" => $this->reportData,
        ]);
    }
}
