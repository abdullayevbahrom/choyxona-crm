<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>{{ $bill->bill_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .center { text-align: center; }
        .mb-6 { margin-bottom: 6px; }
        .mb-10 { margin-bottom: 10px; }
        .line { border-top: 1px dashed #444; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 3px 0; text-align: left; }
        th:last-child, td:last-child { text-align: right; }
        .totals td { padding-top: 2px; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
    @php
        $paymentLabels = [
            'cash' => 'Naqd',
            'card' => 'Karta',
            'transfer' => "O'tkazma",
        ];
    @endphp
    <div class="center mb-10">
        <div class="bold">{{ $setting->company_name }}</div>
        @if($setting->company_address)
            <div>{{ $setting->company_address }}</div>
        @endif
        @if($setting->company_phone)
            <div>{{ $setting->company_phone }}</div>
        @endif
    </div>

    <div class="mb-6">Chek: {{ $bill->bill_number }}</div>
    <div class="mb-6">Sana: {{ $bill->created_at?->format('Y-m-d H:i') }}</div>
    <div class="mb-6">Xona: {{ $bill->room->number }}</div>
    <div class="mb-6">Buyurtma: {{ $bill->order->order_number }}</div>
    <div class="mb-6">Kassir: {{ $bill->order->user?->name ?? 'Noma\'lum' }}</div>
    @php($servedWaiterNames = $bill->order->waiters->pluck('name')->filter()->values())
    @if ($servedWaiterNames->isNotEmpty())
        <div class="mb-6">Xizmat ko'rsatgan ofitsiant(lar): {{ $servedWaiterNames->join(', ') }}</div>
    @endif

    <div class="line"></div>

    <table>
        <thead>
            <tr>
                <th>Mahsulot</th>
                <th>Soni</th>
                <th>Narx</th>
                <th>Jami</th>
                <th>Kiritgan ofitsiant(lar)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bill->order->items as $item)
                <tr>
                    <td>{{ $item->menuItem->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td>{{ number_format((float) $item->subtotal, 2) }}</td>
                    <td>
                        @php($itemWaiterNames = $item->waiters->pluck('name')->filter()->values())
                        {{ $itemWaiterNames->isNotEmpty() ? $itemWaiterNames->join(', ') : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <table class="totals">
        <tr><td>Jami (chegirmasiz)</td><td>{{ number_format((float) $bill->subtotal, 2) }}</td></tr>
        <tr><td>Chegirma</td><td>-{{ number_format((float) ($bill->discount_amount ?? 0), 2) }}</td></tr>
        <tr><td class="bold">Jami</td><td class="bold">{{ number_format((float) $bill->total_amount, 2) }}</td></tr>
    </table>

    <div class="line"></div>

    @if($bill->payment_method)
        <div class="mb-6">To'lov: {{ $paymentLabels[$bill->payment_method] ?? $bill->payment_method }}</div>
    @endif

    <div class="center mb-6">
        <div class="mb-6">QR kod:</div>
        <img src="{{ $qrImageDataUri }}" alt="QR kod" width="100" height="100">
    </div>
    <div class="mb-6">Chek kodi: {{ $bill->bill_number }}</div>

    @if($setting->receipt_footer)
        <div class="center">{{ $setting->receipt_footer }}</div>
    @endif
</body>
</html>
