<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-bold">{{ $order->order_number }}</h1>
                <span id="order-panel-syncing" role="status" aria-live="polite" class="hidden text-xs font-medium text-amber-700">Yangilanmoqda...</span>
            </div>

            @if ($order->status === \App\Models\Order::STATUS_OPEN)
                <a href="{{ route('orders.create', ['room' => $order->room_id]) }}" class="inline-block mb-4 text-blue-700 underline">Menyu orqali mahsulot qo'shish</a>
            @endif

            <div id="order-panel" data-url="{{ route('orders.panel', $order) }}">
                @include('orders.partials.order_panel', ['order' => $order])
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const syncingBadge = document.getElementById('order-panel-syncing');

            window.setupHtmlPolling?.({
                containerId: 'order-panel',
                url: @js(route('orders.panel', $order)),
                fingerprintUrl: @js(route('orders.panel-fingerprint', $order)),
                intervalMs: 10000,
                onStateChange: ({ inFlight }) => {
                    if (!syncingBadge) return;
                    syncingBadge.classList.toggle('hidden', !inFlight);
                },
            });
        });
    </script>
</x-app-layout>
