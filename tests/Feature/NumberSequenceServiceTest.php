<?php

namespace Tests\Feature;

use App\Services\BillService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NumberSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_and_bill_numbers_use_persistent_sequences(): void
    {
        Carbon::setTestNow('2026-02-20 12:00:00');

        $orderService = app(OrderService::class);
        $billService = app(BillService::class);

        $firstOrder = $orderService->nextOrderNumber();
        $secondOrder = $orderService->nextOrderNumber();

        $firstBill = $billService->nextBillNumber();
        $secondBill = $billService->nextBillNumber();

        $this->assertSame('ORD-2026-0001', $firstOrder);
        $this->assertSame('ORD-2026-0002', $secondOrder);

        $this->assertSame('CHK-2026-0001', $firstBill);
        $this->assertSame('CHK-2026-0002', $secondBill);

        $this->assertDatabaseHas('number_sequences', [
            'sequence_key' => 'ORD',
            'year' => 2026,
            'last_value' => 2,
        ]);
        $this->assertDatabaseHas('number_sequences', [
            'sequence_key' => 'CHK',
            'year' => 2026,
            'last_value' => 2,
        ]);

        Carbon::setTestNow();
    }
}
