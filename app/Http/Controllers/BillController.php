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
use SplQueue;
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
            logoSize: $this->resolveQrLogoSize($setting, 68, 16, 96),
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
            logoSize: $this->resolveQrLogoSize($setting, 68, 16, 96),
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

        $preparedLogo = $this->prepareLogoForQr($logoBinary);
        if ($preparedLogo === null) {
            return $svg;
        }

        $targetSize = max(16, min(96, $logoSize));
        $x = (int) floor(($canvasSize - $targetSize) / 2);
        $y = (int) floor(($canvasSize - $targetSize) / 2);
        $encodedLogo = base64_encode($preparedLogo);
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

    private function prepareLogoForQr(string $logoBinary): ?string
    {
        $logoInfo = @getimagesizefromstring($logoBinary);
        if ($logoInfo === false || ($logoInfo['mime'] ?? '') !== 'image/png') {
            return null;
        }

        $image = @imagecreatefromstring($logoBinary);
        if ($image === false) {
            return null;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        $this->makeEdgeWhitePixelsTransparent($image, 246);

        ob_start();
        imagepng($image);
        imagedestroy($image);

        $normalized = (string) ob_get_clean();

        return $normalized !== '' ? $normalized : null;
    }

    private function makeEdgeWhitePixelsTransparent(
        \GdImage $image,
        int $threshold,
    ): void {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < 1 || $height < 1) {
            return;
        }

        $queue = new SplQueue;
        $seen = [];
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);

        $enqueue = function (int $x, int $y) use (
            &$queue,
            &$seen,
            $width,
            $height,
        ): void {
            if ($x < 0 || $y < 0 || $x >= $width || $y >= $height) {
                return;
            }

            $key = $x.':'.$y;
            if (isset($seen[$key])) {
                return;
            }

            $seen[$key] = true;
            $queue->enqueue([$x, $y]);
        };

        for ($x = 0; $x < $width; $x++) {
            $enqueue($x, 0);
            $enqueue($x, $height - 1);
        }
        for ($y = 0; $y < $height; $y++) {
            $enqueue(0, $y);
            $enqueue($width - 1, $y);
        }

        while (! $queue->isEmpty()) {
            [$x, $y] = $queue->dequeue();
            $rgba = imagecolorat($image, $x, $y);

            $alpha = ($rgba & 0x7F000000) >> 24;
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            $isOpaqueEnough = $alpha < 110;
            $isWhiteLike =
                $r >= $threshold && $g >= $threshold && $b >= $threshold;

            if (! $isOpaqueEnough || ! $isWhiteLike) {
                continue;
            }

            imagesetpixel($image, $x, $y, $transparent);

            $enqueue($x + 1, $y);
            $enqueue($x - 1, $y);
            $enqueue($x, $y + 1);
            $enqueue($x, $y - 1);
        }
    }
}
