@php
    $statusLabels = [
        'open' => 'Ochiq',
        'closed' => 'Yopilgan',
        'cancelled' => 'Bekor qilingan',
    ];
@endphp
<p class="mb-4 text-slate-600">Xona: {{ $order->room->number }} | Holat: {{ $statusLabels[$order->status] ?? $order->status }}</p>

<div class="mb-6 overflow-x-auto rounded-xl border bg-white">
    <table class="min-w-[680px] w-full text-sm">
        <thead class="bg-slate-100">
        <tr>
            <th class="p-3 text-left">Mahsulot</th>
            <th class="p-3 text-left">Soni</th>
            <th class="p-3 text-left">Narx</th>
            <th class="p-3 text-left">Jami</th>
            @if ($order->status === 'open')
                <th class="p-3 text-left">Amal</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @forelse ($order->items as $item)
            <tr class="border-t">
                <td class="p-3">{{ $item->menuItem->name }}</td>
                <td class="p-3">
                    @if ($order->status === 'open')
                        <form
                            method="POST"
                            action="{{ route('orders.items.update', [$order, $item]) }}"
                            class="flex items-center gap-2"
                            data-disable-on-submit
                            data-pending-text="Saqlanmoqda..."
                        >
                            @csrf
                            @method('PATCH')
                            <input name="quantity" type="number" min="1" max="1000" value="{{ $item->quantity }}" class="w-16 rounded border p-1 sm:w-20">
                            <button class="text-xs text-blue-700 underline">Saqlash</button>
                        </form>
                    @else
                        {{ $item->quantity }}
                    @endif
                </td>
                <td class="p-3">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="p-3">{{ number_format((float) $item->subtotal, 2) }}</td>
                @if ($order->status === 'open')
                    <td class="p-3">
                        <form
                            method="POST"
                            action="{{ route('orders.items.destroy', [$order, $item]) }}"
                            data-confirm="Ushbu mahsulotni buyurtmadan olib tashlamoqchimisiz?"
                            data-disable-on-submit
                            data-pending-text="O'chirilmoqda..."
                        >
                            @csrf
                            @method('DELETE')
                            <button class="text-xs text-red-600 underline">O'chirish</button>
                        </form>
                    </td>
                @endif
            </tr>
        @empty
            <tr class="border-t">
                <td colspan="{{ $order->status === 'open' ? 5 : 4 }}" class="p-3">Mahsulot yo'q.</td>
            </tr>
        @endforelse
        </tbody>
        <tfoot>
        <tr class="border-t bg-slate-50">
            <td colspan="{{ $order->status === 'open' ? 4 : 3 }}" class="p-3 text-right font-bold">{{ $order->bill ? "Jami (chegirmasiz)" : 'Jami' }}</td>
            <td class="p-3 font-bold">{{ number_format((float) $order->total_amount, 2) }}</td>
        </tr>
        </tfoot>
    </table>
</div>

@if ($order->bill)
    <div class="mb-6 rounded-xl border bg-white p-4">
        <div class="text-sm text-slate-700">
            <p class="font-semibold">Chek: {{ $order->bill->bill_number }}</p>
            <div class="mt-2 max-w-md space-y-1">
                <div class="flex items-center justify-between">
                    <span>Jami (chegirmasiz)</span>
                    <span class="font-medium">{{ number_format((float) $order->bill->subtotal, 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Chegirma ({{ $order->bill->discount_percent !== null ? number_format((float) $order->bill->discount_percent, 2).'%' : '0.00%' }})</span>
                    <span class="font-medium text-red-600">-{{ number_format((float) ($order->bill->discount_amount ?? 0), 2) }}</span>
                </div>
                <div class="border-t pt-2 mt-2 flex items-center justify-between text-base font-semibold text-slate-900">
                    <span>To'lov jami</span>
                    <span>{{ number_format((float) $order->bill->total_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
@endif

@if ($order->status === 'open')
    <form
        method="POST"
        action="{{ route('orders.bill.store', $order) }}"
        class="grid grid-cols-1 gap-3 rounded-xl border bg-white p-4 sm:grid-cols-2 xl:grid-cols-4"
        data-disable-on-submit
        data-pending-text="Chek yaratilmoqda..."
    >
        @csrf
        <select name="payment_method" class="rounded border p-2">
            <option value="">To'lov usuli</option>
            <option value="cash">Naqd</option>
            <option value="card">Karta</option>
            <option value="transfer">O'tkazma</option>
        </select>
        <input name="discount_percent" type="number" step="0.01" min="0" max="100" placeholder="Chegirma %" class="rounded border p-2">
        <input name="discount_amount" type="number" step="0.01" min="0" placeholder="Chegirma summa" class="rounded border p-2">
        <button class="rounded bg-slate-900 p-2 text-white">Chek yaratish</button>
    </form>

    <form
        method="POST"
        action="{{ route('orders.cancel', $order) }}"
        class="mt-3"
        data-confirm="Buyurtmani bekor qilmoqchimisiz?"
        data-disable-on-submit
        data-pending-text="Bekor qilinmoqda..."
    >
        @csrf
        <button class="rounded bg-red-700 px-4 py-2 text-white">Buyurtmani bekor qilish</button>
    </form>
@endif
