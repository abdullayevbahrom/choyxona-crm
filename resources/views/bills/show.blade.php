<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-2">{{ $setting->company_name }}</h1>
            @if($setting->company_address || $setting->company_phone)
                <p class="text-slate-600 mb-2">
                    {{ $setting->company_address }}
                    @if($setting->company_address && $setting->company_phone) | @endif
                    {{ $setting->company_phone }}
                </p>
            @endif
            <h2 class="text-xl font-semibold mb-2">Chek: {{ $bill->bill_number }}</h2>
            <p class="text-slate-600 mb-4">Buyurtma: {{ $bill->order->order_number }} | Xona: {{ $bill->room->number }}</p>
            <p class="text-slate-600 mb-4">Kassir: {{ $bill->order->user?->name ?? 'Noma\'lum' }}</p>

            <div class="bg-white rounded-xl border p-4 mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-[640px] w-full text-sm mb-4">
                <thead>
                <tr class="border-b">
                    <th class="text-left p-2">Mahsulot</th>
                    <th class="text-left p-2">Soni</th>
                    <th class="text-left p-2">Narx</th>
                    <th class="text-left p-2">Jami</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($bill->order->items as $item)
                    <tr class="border-b">
                        <td class="p-2">{{ $item->menuItem->name }}</td>
                        <td class="p-2">{{ $item->quantity }}</td>
                        <td class="p-2">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="p-2">{{ number_format((float) $item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-right space-y-1">
            <div>Subtotal: {{ number_format((float) $bill->subtotal, 2) }}</div>
            <div>Chegirma: {{ $bill->discount_amount !== null ? number_format((float) $bill->discount_amount, 2) : '0.00' }}</div>
            <div class="text-lg font-bold">Jami: {{ number_format((float) $bill->total_amount, 2) }}</div>
        </div>

        <div class="mt-4 border-t pt-4">
            <p class="text-sm font-medium text-slate-700 mb-2">QR (tekshirish ma'lumoti)</p>
            <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                <img src="{{ $qrImageUrl }}" alt="Bill QR" width="120" height="120" class="rounded border">
                <p class="text-xs text-slate-600 break-all">{{ $qrPayload }}</p>
            </div>
        </div>
            </div>

            <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('bills.pdf', $bill) }}" target="_blank" class="bg-slate-900 text-white rounded px-4 py-2">
                    PDF ochish
                </a>

                @if (! $bill->is_printed)
                    <form
                        method="POST"
                        action="{{ route('bills.print', $bill) }}"
                        data-confirm="Chekni chop etib buyurtmani yopmoqchimisiz?"
                        data-disable-on-submit
                        data-pending-text="Chop etilmoqda..."
                    >
                        @csrf
                        <button class="bg-green-700 text-white rounded px-4 py-2">Chek print (yopish)</button>
                    </form>
                @else
                    <p class="text-green-700 font-semibold">Chek chop etilgan.</p>
                @endif
            </div>

            @if($setting->receipt_footer)
                <p class="mt-6 text-sm text-slate-600">{{ $setting->receipt_footer }}</p>
            @endif
        </div>
    </div>
</x-app-layout>
