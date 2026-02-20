<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Order;
use App\Models\Setting;
use App\Services\BillService;
use App\Support\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class BillController extends Controller
{
    public function __construct(private readonly BillService $billService) {}

    public function store(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            "payment_method" => ["nullable", "in:cash,card,transfer"],
            "discount_percent" => [
                "nullable",
                "numeric",
                "min:0",
                "max:100",
                "prohibits:discount_amount",
            ],
            "discount_amount" => [
                "nullable",
                "numeric",
                "min:0",
                "prohibits:discount_percent",
            ],
        ]);

        try {
            $bill = $this->billService->createForOrder(
                $order,
                $validated["payment_method"] ?? null,
                isset($validated["discount_percent"])
                    ? (float) $validated["discount_percent"]
                    : null,
                isset($validated["discount_amount"])
                    ? (float) $validated["discount_amount"]
                    : null,
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                "order" => $e->getMessage(),
            ]);
        }
        ActivityLogger::log("bills.create", $bill, "Chek yaratildi.");

        return redirect()->route("bills.show", $bill);
    }

    public function show(Bill $bill): View
    {
        $bill->load(["order.room", "order.user", "order.items.menuItem"]);
        $setting = Setting::current();

        return view("bills.show", compact("bill", "setting"));
    }

    public function pdf(Bill $bill): Response
    {
        $bill->load(["order.room", "order.user", "order.items.menuItem"]);
        $setting = Setting::current();

        $pdf = Pdf::loadView("bills.pdf", [
            "bill" => $bill,
            "setting" => $setting,
        ])->setPaper("a6");

        return $pdf->stream($bill->bill_number . ".pdf");
    }

    public function print(Bill $bill): RedirectResponse
    {
        $this->billService->markAsPrinted($bill);
        ActivityLogger::log("bills.print", $bill, "Chek chop etildi.");

        return redirect()
            ->route("dashboard")
            ->with("status", 'Chek chop etildi, xona bo\'shatildi.');
    }
}
