<?php

namespace App\Http\Controllers;

use App\Http\Requests\Bills\BillStoreRequest;
use App\Models\Bill;
use App\Models\Order;
use App\Models\Setting;
use App\Services\BillService;
use App\Support\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

class BillController extends Controller
{
    public function __construct(private readonly BillService $billService) {}

    public function store(
        BillStoreRequest $request,
        Order $order,
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $bill = $this->billService->createForOrder(
                $order,
                $validated['payment_method'] ?? null,
                isset($validated['discount_percent'])
                    ? (float) $validated['discount_percent']
                    : null,
                isset($validated['discount_amount'])
                    ? (float) $validated['discount_amount']
                    : null,
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'order' => $e->getMessage(),
            ]);
        }
        ActivityLogger::log('bills.create', $bill, 'Chek yaratildi.');

        return redirect()->route('bills.show', $bill);
    }

    public function show(Bill $bill): View
    {
        $bill->load([
            'order.room',
            'order.user',
            'order.waiters:id,name',
            'order.items.menuItem',
            'order.items.waiters:id,name',
        ]);
        $setting = Setting::current();
        $qrPayload = $this->buildQrPayload($bill);
        $qrLogoPath = $this->resolveQrLogoPath($setting);
        $qrImageDataUri = $this->buildQrImageDataUri(
            payload: $qrPayload,
            size: 140,
            logoPath: $qrLogoPath,
            logoSize: $this->resolveQrLogoSize($setting, 28, 16, 48),
        );

        return view('bills.show', [
            'bill' => $bill,
            'setting' => $setting,
            'qrImageDataUri' => $qrImageDataUri,
        ]);
    }

    public function pdf(Bill $bill): Response
    {
        $bill->load([
            'order.room',
            'order.user',
            'order.waiters:id,name',
            'order.items.menuItem',
            'order.items.waiters:id,name',
        ]);
        $setting = Setting::current();
        $qrPayload = $this->buildQrPayload($bill);
        $qrLogoPath = $this->resolveQrLogoPath($setting);
        $qrImageDataUri = $this->buildQrImageDataUri(
            payload: $qrPayload,
            size: 120,
            logoPath: $qrLogoPath,
            logoSize: $this->resolveQrLogoSize($setting, 24, 16, 36),
        );

        $pdf = Pdf::loadView('bills.pdf', [
            'bill' => $bill,
            'setting' => $setting,
            'qrImageDataUri' => $qrImageDataUri,
        ])->setPaper('a6');

        return $pdf->stream($bill->bill_number.'.pdf');
    }

    public function print(Bill $bill): RedirectResponse
    {
        $this->billService->markAsPrinted($bill);
        ActivityLogger::log('bills.print', $bill, 'Chek chop etildi.');

        return redirect()
            ->route('dashboard')
            ->with('status', 'Chek chop etildi, xona bo\'shatildi.');
    }

    public function verify(Request $request, Bill $bill): View
    {
        $bill->load(['room:id,number', 'order:id,order_number,status']);

        return view('bills.verify', [
            'bill' => $bill,
        ]);
    }

    private function buildQrPayload(Bill $bill): string
    {
        return URL::signedRoute(
            'bills.verify',
            ['bill' => $bill->id],
            null,
            false,
        );
    }

    private function buildQrImageDataUri(
        string $payload,
        int $size,
        ?string $logoPath = null,
        ?int $logoSize = null,
    ): string {
        $normalizedSize = max(80, min(300, $size));
        $svg = (string) QrCode::format('svg')
            ->size($normalizedSize)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($payload);

        if ($logoPath !== null && is_file($logoPath) && $logoSize !== null) {
            $svg = $this->embedPngLogoOnQrSvg(
                svg: $svg,
                logoPath: $logoPath,
                logoSize: $logoSize,
                canvasSize: $normalizedSize,
            );
        }

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function resolveQrLogoPath(Setting $setting): ?string
    {
        $configured = (string) $setting->notification_logo_url;
        if ($configured !== '') {
            $configuredPath = $this->extractPublicPathFromUrl($configured);
            if ($configuredPath !== null) {
                $absolute = Storage::disk('public')->path($configuredPath);
                if (is_file($absolute)) {
                    return $absolute;
                }
            }
        }

        $fallback = public_path('qr-logo-fallback.png');

        return is_file($fallback) ? $fallback : null;
    }

    private function resolveQrLogoSize(
        Setting $setting,
        int $default,
        int $min,
        int $max,
    ): int {
        $size = (int) ($setting->notification_logo_size ?? $default);

        if ($size < $min) {
            return $min;
        }

        if ($size > $max) {
            return $max;
        }

        return $size;
    }

    private function extractPublicPathFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (! str_starts_with($path, '/storage/')) {
            return null;
        }

        return ltrim(substr($path, strlen('/storage/')), '/');
    }

    private function embedPngLogoOnQrSvg(
        string $svg,
        string $logoPath,
        int $logoSize,
        int $canvasSize,
    ): string {
        $logoBinary = @file_get_contents($logoPath);
        if ($logoBinary === false || $logoBinary === '') {
            return $svg;
        }

        $logoInfo = @getimagesizefromstring($logoBinary);
        if ($logoInfo === false || ($logoInfo['mime'] ?? '') !== 'image/png') {
            return $svg;
        }

        $targetSize = max(16, min(48, $logoSize));
        $x = (int) floor(($canvasSize - $targetSize) / 2);
        $y = (int) floor(($canvasSize - $targetSize) / 2);
        $encodedLogo = base64_encode($logoBinary);
        $overlay = sprintf(
            '<image x="%d" y="%d" width="%d" height="%d" href="data:image/png;base64,%s" preserveAspectRatio="xMidYMid slice" />',
            $x,
            $y,
            $targetSize,
            $targetSize,
            $encodedLogo,
        );

        $needle = '</svg>';
        if (! str_contains($svg, $needle)) {
            return $svg;
        }

        return str_replace($needle, $overlay.$needle, $svg);
    }
}
