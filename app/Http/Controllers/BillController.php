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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
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
        $bill->load(['order.room', 'order.user', 'order.items.menuItem']);
        $setting = Setting::current();
        $qrPayload = $this->buildQrPayload($bill);

        return view('bills.show', [
            'bill' => $bill,
            'setting' => $setting,
            'qrPayload' => $qrPayload,
            'qrImageUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data='.
                rawurlencode($qrPayload),
        ]);
    }

    public function pdf(Bill $bill): Response
    {
        $bill->load(['order.room', 'order.user', 'order.items.menuItem']);
        $setting = Setting::current();
        $qrPayload = $this->buildQrPayload($bill);

        $pdf = Pdf::loadView('bills.pdf', [
            'bill' => $bill,
            'setting' => $setting,
            'qrPayload' => $qrPayload,
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

    private function buildQrPayload(Bill $bill): string
    {
        return implode('|', [
            'bill='.$bill->bill_number,
            'order='.$bill->order->order_number,
            'room='.$bill->room->number,
            'total='.number_format((float) $bill->total_amount, 2, '.', ''),
            'date='.
            ($bill->created_at?->format('Y-m-d H:i') ??
                now()->format('Y-m-d H:i')),
        ]);
    }
}
