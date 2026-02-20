<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Order;
use App\Models\Room;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BillService
{
    public function __construct(
        private readonly NumberSequenceService $numberSequenceService,
    ) {}

    public function createForOrder(
        Order $order,
        ?string $paymentMethod = null,
        ?float $discountPercent = null,
        ?float $discountAmount = null,
    ): Bill {
        if ($order->status !== Order::STATUS_OPEN) {
            throw new RuntimeException(
                'Chek faqat ochiq buyurtma uchun yaratiladi.',
            );
        }

        $itemsSummary = $order
            ->items()
            ->selectRaw(
                'count(*) as items_count, coalesce(sum(subtotal), 0) as subtotal',
            )
            ->first();

        $itemsCount = (int) ($itemsSummary?->items_count ?? 0);
        $subtotal = (float) ($itemsSummary?->subtotal ?? 0);

        if ($itemsCount === 0) {
            throw new RuntimeException(
                'Kamida bitta mahsulot bo\'lmasa, buyurtmani yopib bo\'lmaydi.',
            );
        }

        if ($order->bill()->exists()) {
            throw new RuntimeException(
                'Bu buyurtma uchun chek allaqachon yaratilgan.',
            );
        }

        [$finalPercent, $finalAmount] = $this->resolveDiscounts(
            $subtotal,
            $discountPercent,
            $discountAmount,
        );
        $total = max(0, $subtotal - $finalAmount);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return DB::transaction(function () use (
                    $order,
                    $paymentMethod,
                    $subtotal,
                    $finalPercent,
                    $finalAmount,
                    $total,
                ) {
                    $bill = Bill::query()->create([
                        'order_id' => $order->id,
                        'room_id' => $order->room_id,
                        'bill_number' => $this->nextBillNumber(),
                        'subtotal' => $subtotal,
                        'discount_percent' => $finalPercent,
                        'discount_amount' => $finalAmount,
                        'total_amount' => $total,
                        'payment_method' => $paymentMethod,
                        'is_printed' => false,
                    ]);

                    return $bill;
                });
            } catch (QueryException $e) {
                if (! $this->isDuplicateBillNumberError($e) || $attempt === 4) {
                    throw $e;
                }
            }
        }

        throw new RuntimeException('Chek raqami yaratishda xatolik yuz berdi.');
    }

    public function markAsPrinted(Bill $bill): void
    {
        DB::transaction(function () use ($bill) {
            $bill->update([
                'is_printed' => true,
                'printed_at' => now(),
            ]);

            $order = $bill->order()->firstOrFail();
            $order->update([
                'status' => Order::STATUS_CLOSED,
                'closed_at' => now(),
            ]);

            Room::query()
                ->whereKey($order->room_id)
                ->update([
                    'status' => Room::STATUS_EMPTY,
                ]);
        });
    }

    public function nextBillNumber(): string
    {
        $year = (int) now()->format('Y');
        $prefix = "CHK-{$year}-";
        $next = $this->numberSequenceService->next('CHK', $year);

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function resolveDiscounts(
        float $subtotal,
        ?float $discountPercent,
        ?float $discountAmount,
    ): array {
        if ($discountPercent !== null) {
            if ($discountPercent < 0 || $discountPercent > 100) {
                throw new RuntimeException(
                    'Chegirma foizi 0% dan 100% gacha bo\'lishi kerak.',
                );
            }

            $amount = $subtotal * ($discountPercent / 100);

            return [$discountPercent, $amount];
        }

        if ($discountAmount !== null) {
            if ($discountAmount < 0 || $discountAmount > $subtotal) {
                throw new RuntimeException('Chegirma summasi noto\'g\'ri.');
            }

            $percent = $subtotal > 0 ? ($discountAmount / $subtotal) * 100 : 0;

            return [$percent, $discountAmount];
        }

        return [null, null];
    }

    private function isDuplicateBillNumberError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'bills.bill_number') ||
            str_contains($e->getMessage(), 'bills_bill_number_unique') ||
            str_contains(
                $e->getMessage(),
                'UNIQUE constraint failed: bills.bill_number',
            );
    }
}
