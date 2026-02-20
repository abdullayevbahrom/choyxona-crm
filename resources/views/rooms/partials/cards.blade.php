<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @foreach ($rooms as $room)
        @php $openOrder = $room->openOrder; @endphp
        <a href="{{ $openOrder ? route('orders.show', $openOrder) : route('orders.create', ['room' => $room->id]) }}"
           class="rounded-xl border p-4 shadow-sm transition hover:shadow-lg {{ $room->status === 'occupied' ? 'bg-green-100 border-green-400' : 'bg-amber-200 border-amber-400' }}">
            <div class="text-center text-3xl font-black sm:text-4xl">{{ $room->number }}</div>
            <div class="text-center text-sm text-slate-700">{{ $room->name ?? 'Nomsiz xona' }}</div>
            <div class="mt-3 text-center">
                <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $room->status === 'occupied' ? 'bg-green-500 text-white' : 'bg-amber-500 text-white' }}">
                    {{ $room->status === 'occupied' ? 'BAND' : 'BO\'SH' }}
                </span>
            </div>
            @if ($openOrder)
                <div class="mt-3 text-sm text-slate-700">Buyurtma: {{ $openOrder->order_number }}</div>
                <div class="text-sm font-semibold">Jami: {{ number_format((float) $openOrder->total_amount, 2) }}</div>
                <div class="text-xs text-slate-600">{{ $openOrder->opened_at?->diffForHumans() }}</div>
            @endif
        </a>
    @endforeach
</div>
