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
        $qrImageDataUri = $this->buildQrImageDataUri($qrPayload, 140);

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
        $qrImageDataUri = $this->buildQrImageDataUri($qrPayload, 120);

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
        return URL::signedRoute('bills.verify', ['bill' => $bill->id]);
    }

    private function buildQrImageDataUri(string $payload, int $size): string
    {
        $normalizedSize = max(80, min(300, $size));
        $svg = QrCode::format('svg')
            ->size($normalizedSize)
            ->margin(1)
            ->generate($payload);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
