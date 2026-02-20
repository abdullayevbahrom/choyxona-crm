<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLogs\ActivityLogFilterRequest;
use App\Jobs\GenerateActivityLogExport;
use App\Models\ActivityLog;
use App\Models\ActivityLogExport;
use App\Models\User;
use App\Services\ActivityLogQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogQueryService $queryService,
    ) {}

    public function index(ActivityLogFilterRequest $request): View
    {
        $validated = $request->validated();

        $query = $this->queryService->build($validated);

        $subjectTypes = ActivityLog::query()
            ->select("subject_type")
            ->whereNotNull("subject_type")
            ->distinct()
            ->orderBy("subject_type")
            ->pluck("subject_type");

        $quickActions = ActivityLog::query()
            ->select("action")
            ->groupBy("action")
            ->orderByRaw("max(id) desc")
            ->limit(12)
            ->pluck("action");

        $exports = ActivityLogExport::query()
            ->where("user_id", auth()->id())
            ->latest("id")
            ->limit(10)
            ->get();

        return view("activity-logs.index", [
            "logs" => $query
                ->paginate(40)
                ->withQueryString()
                ->through(function (ActivityLog $log) {
                    $log->subject_url = $this->resolveSubjectUrl($log);
                    return $log;
                }),
            "users" => User::query()
                ->orderBy("name")
                ->get(["id", "name"]),
            "subjectTypes" => $subjectTypes,
            "quickActions" => $quickActions,
            "exports" => $exports,
            "filters" => $validated,
        ]);
    }

    public function exportCsv(
        ActivityLogFilterRequest $request,
    ): StreamedResponse {
        $validated = $request->validated();

        $filename = "activity-logs-" . now()->format("Ymd-His") . ".csv";

        return response()->streamDownload(
            function () use ($validated) {
                $handle = fopen("php://output", "w");

                fputcsv($handle, [
                    "id",
                    "created_at",
                    "user",
                    "action",
                    "subject_type",
                    "subject_id",
                    "description",
                    "ip_address",
                    "properties",
                ]);

                $this->queryService
                    ->build($validated)
                    ->with("user")
                    ->chunk(500, function ($logs) use ($handle) {
                        foreach ($logs as $log) {
                            fputcsv(
                                $handle,
                                $this->sanitizeCsvRow([
                                    $log->id,
                                    $log->created_at?->format("Y-m-d H:i:s"),
                                    $log->user?->name,
                                    $log->action,
                                    $log->subject_type,
                                    $log->subject_id,
                                    $log->description,
                                    $log->ip_address,
                                    json_encode(
                                        $log->properties,
                                        JSON_UNESCAPED_UNICODE,
                                    ),
                                ]),
                            );
                        }
                    });

                fclose($handle);
            },
            $filename,
            [
                "Content-Type" => "text/csv; charset=UTF-8",
            ],
        );
    }

    public function requestExport(
        ActivityLogFilterRequest $request,
    ): RedirectResponse {
        $validated = $request->validated();

        $export = ActivityLogExport::query()->create([
            "user_id" => (int) auth()->id(),
            "status" => ActivityLogExport::STATUS_PENDING,
            "filters" => $validated,
        ]);

        GenerateActivityLogExport::dispatch($export->id);

        return back()->with("status", 'Export navbatga qo\'yildi.');
    }

    public function downloadExport(
        ActivityLogExport $export,
    ): BinaryFileResponse|RedirectResponse {
        if ($export->user_id !== auth()->id()) {
            abort(403);
        }

        if (
            $export->status !== ActivityLogExport::STATUS_READY ||
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

    private function resolveSubjectUrl(ActivityLog $log): ?string
    {
        if (!$log->subject_type || !$log->subject_id) {
            return null;
        }

        return match ($log->subject_type) {
            \App\Models\Order::class => route("orders.show", $log->subject_id),
            \App\Models\Bill::class => route("bills.show", $log->subject_id),
            \App\Models\Room::class => route("rooms.index"),
            \App\Models\MenuItem::class => route("menu.index"),
            \App\Models\Setting::class => route("settings.index"),
            default => null,
        };
    }

    private function sanitizeCsvRow(array $row): array
    {
        return array_map(function ($value) {
            if (!is_string($value) || $value === "") {
                return $value;
            }

            if (in_array($value[0], ["=", "+", "-", "@"], true)) {
                return "'" . $value;
            }

            return $value;
        }, $row);
    }
}
