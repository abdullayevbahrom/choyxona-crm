<x-app-layout>
    <h1 class="text-2xl font-bold mb-2">{{ $order->order_number }}</h1>

    <a href="{{ route('orders.create', ['room' => $order->room_id]) }}" class="inline-block mb-4 text-blue-700 underline">Menyu orqali mahsulot qo'shish</a>

    <div id="order-panel" data-url="{{ route('orders.panel', $order) }}">
        @include('orders.partials.order_panel', ['order' => $order])
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.setupHtmlPolling?.({
                containerId: 'order-panel',
                url: @js(route('orders.panel', $order)),
                fingerprintUrl: @js(route('orders.panel-fingerprint', $order)),
                intervalMs: 10000,
            });
        });
    </script>
</x-app-layout>
