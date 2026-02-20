<x-app-layout>
    <h1 class="text-2xl font-bold mb-4">Xona {{ $room->number }} uchun buyurtma</h1>

    <div id="create-status" data-url="{{ route('orders.create.status', ['room' => $room->id]) }}">
        @include('orders.partials.create_status', ['room' => $room, 'openOrder' => $openOrder])
    </div>

    <form method="GET" class="bg-white rounded-xl border p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="hidden" name="room" value="{{ $room->id }}">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Qidirish" class="border rounded p-2">
        <select name="type" class="border rounded p-2">
            <option value="">Barchasi</option>
            @foreach (['food' => 'Taom', 'drink' => 'Ichimlik', 'bread' => 'Non', 'salad' => 'Salat', 'sauce' => 'Sous'] as $key => $label)
                <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="bg-slate-900 text-white rounded p-2">Filter</button>
    </form>

    <div class="bg-white rounded-xl border p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach ($menuItems as $item)
                <div class="border rounded-lg p-3">
                    <div class="font-semibold">{{ $item->name }}</div>
                    <div class="text-sm text-slate-600">{{ $item->type }}</div>
                    <div class="mb-2">{{ $item->price !== null ? number_format((float) $item->price, 2) : 'Narx yo\'q' }}</div>

                    <form
                        method="POST"
                        action="{{ $openOrder ? route('orders.items.store', $openOrder) : '#' }}"
                        class="flex gap-2"
                        data-menu-item-form
                    >
                        @csrf
                        <input type="hidden" name="menu_item_id" value="{{ $item->id }}">
                        <input
                            name="quantity"
                            type="number"
                            min="1"
                            value="1"
                            class="border rounded p-1 w-20"
                            data-quantity-input
                            @disabled(! $openOrder)
                        >
                        <button
                            class="bg-green-700 text-white rounded px-3 disabled:opacity-50"
                            data-add-button
                            @disabled(! $openOrder)
                        >
                            Qo'shish
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const actionTemplate = @js(url('/orders/__ORDER_ID__/items'));

            const syncMenuItemForms = () => {
                const statusContainer = document.getElementById('create-status');
                if (!statusContainer) return;

                const marker = statusContainer.querySelector('[data-open-order-id]');
                const orderId = marker?.dataset?.openOrderId?.trim() || '';
                const forms = document.querySelectorAll('form[data-menu-item-form]');

                forms.forEach((form) => {
                    const button = form.querySelector('[data-add-button]');
                    const quantityInput = form.querySelector('[data-quantity-input]');

                    if (!orderId) {
                        form.setAttribute('action', '#');
                        if (button) button.disabled = true;
                        if (quantityInput) quantityInput.disabled = true;
                        return;
                    }

                    form.setAttribute('action', actionTemplate.replace('__ORDER_ID__', orderId));
                    if (button) button.disabled = false;
                    if (quantityInput) quantityInput.disabled = false;
                });
            };

            syncMenuItemForms();

            window.setupHtmlPolling?.({
                containerId: 'create-status',
                url: @js(route('orders.create.status', ['room' => $room->id])),
                fingerprintUrl: @js(route('orders.create.status-fingerprint', ['room' => $room->id])),
                intervalMs: 10000,
                afterUpdate: syncMenuItemForms,
            });
        });
    </script>
</x-app-layout>
