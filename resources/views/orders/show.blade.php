<x-app-layout>
    <div class="mb-2 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">{{ $order->order_number }}</h1>
        <span id="order-panel-syncing" class="hidden text-xs font-medium text-amber-700">Yangilanmoqda...</span>
    </div>

    <a href="{{ route('orders.create', ['room' => $order->room_id]) }}" class="inline-block mb-4 text-blue-700 underline">Menyu orqali mahsulot qo'shish</a>

    <div id="order-panel" data-url="{{ route('orders.panel', $order) }}">
        @include('orders.partials.order_panel', ['order' => $order])
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
