<?php

namespace App\Http\Controllers;

use App\Exports\ReportStreamExport;
use App\Http\Requests\Reports\ReportFilterRequest;
use App\Models\Room;
use App\Models\User;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index(ReportFilterRequest $request): View
    {
        $filters = $request->validated();
        $reportData = $this->reportService->getReportData($filters);

        return view("reports.index", [
            "filters" => $filters,
            "rooms" => Room::query()->orderBy("number")->get(["id", "number"]),
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

    public function exportCsv(ReportFilterRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $filename = "reports-" . now()->format("Ymd-His") . ".csv";

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen("php://output", "wb");

            if ($handle === false) {
                return;
            }

            $this->reportService->streamCsv($filters, $handle);
            fclose($handle);
        }, $filename, [
            "Content-Type" => "text/csv; charset=UTF-8",
        ]);
    }

    public function exportXls(ReportFilterRequest $request): Response
    {
        return Excel::download(
            new ReportStreamExport($request->validated()),
            "reports-" . now()->format("Ymd-His") . ".xlsx",
        );
    }

    public function exportPdf(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $reportData = $this->reportService->getReportData($filters);

        $pdf = Pdf::loadView("reports.pdf", [
            "filters" => $filters,
            "reportData" => $reportData,
        ])->setPaper("a4");

        return $pdf->download("reports-" . now()->format("Ymd-His") . ".pdf");
    }
}
