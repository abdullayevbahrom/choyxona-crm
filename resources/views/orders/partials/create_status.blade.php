<div data-open-order-id="{{ $openOrder?->id ?? '' }}">
    @if (! $openOrder)
        <form method="POST" action="{{ route('orders.store') }}" class="mb-6">
            @csrf
            <input type="hidden" name="room_id" value="{{ $room->id }}">
            <textarea name="notes" placeholder="Izoh" class="mb-2 w-full rounded border p-2"></textarea>
            <button class="rounded bg-green-700 px-4 py-2 text-white">Yangi buyurtma ochish</button>
        </form>
    @else
        <a class="mb-6 inline-block text-blue-700 underline" href="{{ route('orders.show', $openOrder) }}">
            Ochiq buyurtmaga o'tish: {{ $openOrder->order_number }}
        </a>
    @endif
</div>
