<?php

namespace App\Http\Controllers;

use App\Exports\ReportStreamExport;
use App\Http\Requests\Reports\ReportExportStatusesRequest;
use App\Http\Requests\Reports\ReportFilterRequest;
use App\Jobs\GenerateReportExport;
use App\Models\Room;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    public function index(ReportFilterRequest $request): View
    {
        $filters = $request->validated();
        $reportData = $this->reportService->getReportData($filters);
        $exports = ReportExport::query()
            ->where("user_id", (int) auth()->id())
            ->latest("id")
            ->limit(10)
            ->get();

        return view("reports.index", [
            "filters" => $filters,
            "rooms" => Room::query()
                ->orderBy("number")
                ->get(["id", "number"]),
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
            "exports" => $exports,
        ]);
    }

    public function exportCsv(ReportFilterRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $filename = "reports-" . now()->format("Ymd-His") . ".csv";

        return response()->streamDownload(
            function () use ($filters): void {
                $handle = fopen("php://output", "wb");

                if ($handle === false) {
                    return;
                }

                $this->reportService->streamCsv($filters, $handle);
                fclose($handle);
            },
            $filename,
            [
                "Content-Type" => "text/csv; charset=UTF-8",
            ],
        );
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

    public function requestExport(
        ReportFilterRequest $request,
    ): RedirectResponse {
        $export = ReportExport::query()->create([
            "user_id" => (int) auth()->id(),
            "status" => ReportExport::STATUS_PENDING,
            "filters" => $request->validated(),
            "format" => "csv",
        ]);

        GenerateReportExport::dispatch($export->id);

        return back()->with("status", "Report export navbatga qo'yildi.");
    }

    public function downloadExport(
        ReportExport $export,
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse {
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        if (
            $user->role !== User::ROLE_ADMIN &&
            $export->user_id !== $user->id
        ) {
            abort(403);
        }

        if (
            $export->status !== ReportExport::STATUS_READY ||
            !$export->file_path
        ) {
            return back()->withErrors([
                "export" => "Fayl hali tayyor emas.",
            ]);
        }

        if (!Storage::disk("local")->exists($export->file_path)) {
            return back()->withErrors([
                "export" => "Fayl topilmadi.",
            ]);
        }

        return response()->download(
            Storage::disk("local")->path($export->file_path),
            basename($export->file_path),
            ["Content-Type" => "text/csv; charset=UTF-8"],
        );
    }

    public function exportStatus(
        Request $request,
        ReportExport $export,
    ): JsonResponse|Response {
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        if (
            $user->role !== User::ROLE_ADMIN &&
            $export->user_id !== $user->id
        ) {
            abort(403);
        }

        $payload = [
            "id" => $export->id,
            "status" => $export->status,
            "format" => $export->format,
            "created_at" => $export->created_at?->toIso8601String(),
            "finished_at" => $export->finished_at?->toIso8601String(),
            "error_message" => $export->error_message,
            "download_url" =>
                $export->status === ReportExport::STATUS_READY
                    ? route("reports.exports.download", $export)
                    : null,
        ];

        $etag = $this->reportExportEtag($payload);
        $notModifiedResponse = response("", 200)
            ->setEtag($etag)
            ->header("Cache-Control", "private, must-revalidate, max-age=0");

        if ($notModifiedResponse->isNotModified($request)) {
            return $notModifiedResponse;
        }

        return response()
            ->json($payload)
            ->setEtag($etag)
            ->header("Cache-Control", "private, must-revalidate, max-age=0");
    }

    public function exportStatuses(
        ReportExportStatusesRequest $request,
    ): JsonResponse|Response {
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        $ids = collect($request->validated("ids"))
            ->map(fn($id) => (int) $id)
            ->all();

        $query = ReportExport::query()->whereIn("id", $ids);

        if ($user->role !== User::ROLE_ADMIN) {
            $query->where("user_id", $user->id);
        }

        $exports = $query
            ->orderBy("id")
            ->get([
                "id",
                "status",
                "format",
                "error_message",
                "created_at",
                "finished_at",
            ]);

        $payload = [
            "exports" => $exports
                ->map(
                    fn(ReportExport $export) => [
                        "id" => $export->id,
                        "status" => $export->status,
                        "format" => $export->format,
                        "created_at" => $export->created_at?->toIso8601String(),
                        "finished_at" => $export->finished_at?->toIso8601String(),
                        "error_message" => $export->error_message,
                        "download_url" =>
                            $export->status === ReportExport::STATUS_READY
                                ? route("reports.exports.download", $export)
                                : null,
                    ],
                )
                ->values()
                ->all(),
        ];

        $etag = $this->reportExportEtag($payload);
        $notModifiedResponse = response("", 200)
            ->setEtag($etag)
            ->header("Cache-Control", "private, must-revalidate, max-age=0");

        if ($notModifiedResponse->isNotModified($request)) {
            return $notModifiedResponse;
        }

        return response()
            ->json($payload)
            ->setEtag($etag)
            ->header("Cache-Control", "private, must-revalidate, max-age=0");
    }

    private function reportExportEtag(array $payload): string
    {
        return sha1(json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
