<x-app-layout>
    <h1 class="text-2xl font-bold mb-4">Xona {{ $room->number }} uchun buyurtma</h1>

    <div id="create-status" data-url="{{ route('orders.create.status', ['room' => $room->id]) }}">
        @include('orders.partials.create_status', ['room' => $room, 'openOrder' => $openOrder])
    </div>

    <form id="menu-filter-form" method="GET" class="bg-white rounded-xl border p-4 mb-4 space-y-3">
        <input type="hidden" name="room" value="{{ $room->id }}">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input
                id="menu-search-input"
                name="q"
                value="{{ $filters['q'] ?? '' }}"
                placeholder="Qidirish (real-time)"
                class="border rounded p-2 md:col-span-2"
            >
            <select id="menu-type-select" name="type" class="border rounded p-2">
                <option value="">Barchasi</option>
                @foreach (['food' => 'Taom', 'drink' => 'Ichimlik', 'bread' => 'Non', 'salad' => 'Salat', 'sauce' => 'Sous'] as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap gap-2">
            @php
                $currentQ = $filters['q'] ?? '';
                $currentType = $filters['type'] ?? '';
                $typeLinks = ['' => 'Barchasi', 'food' => 'Taom', 'drink' => 'Ichimlik', 'bread' => 'Non', 'salat' => 'Salat', 'sauce' => 'Sous'];
            @endphp
            @foreach ($typeLinks as $typeKey => $typeLabel)
                <a
                    href="{{ route('orders.create', ['room' => $room->id, 'q' => $currentQ, 'type' => $typeKey ?: null]) }}"
                    class="rounded-full border px-3 py-1 text-sm {{ $currentType === $typeKey ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700' }}"
                >
                    {{ $typeLabel }}
                </a>
            @endforeach
        </div>
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
                        data-disable-on-submit
                        data-pending-text="Qo'shilmoqda..."
                    >
                        @csrf
                        <input type="hidden" name="menu_item_id" value="{{ $item->id }}">
                        <button
                            type="button"
                            class="rounded border px-2"
                            data-minus-button
                            @disabled(! $openOrder)
                        >-</button>
                        <input
                            name="quantity"
                            type="number"
                            min="1"
                            value="1"
                            class="border rounded p-1 w-16 text-center"
                            data-quantity-input
                            @disabled(! $openOrder)
                        >
                        <button
                            type="button"
                            class="rounded border px-2"
                            data-plus-button
                            @disabled(! $openOrder)
                        >+</button>
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
            const filterForm = document.getElementById('menu-filter-form');
            const searchInput = document.getElementById('menu-search-input');
            const typeSelect = document.getElementById('menu-type-select');
            let filterDebounce;

            const syncMenuItemForms = () => {
                const statusContainer = document.getElementById('create-status');
                if (!statusContainer) return;

                const marker = statusContainer.querySelector('[data-open-order-id]');
                const orderId = marker?.dataset?.openOrderId?.trim() || '';
                const forms = document.querySelectorAll('form[data-menu-item-form]');

                forms.forEach((form) => {
                    const button = form.querySelector('[data-add-button]');
                    const quantityInput = form.querySelector('[data-quantity-input]');
                    const minusButton = form.querySelector('[data-minus-button]');
                    const plusButton = form.querySelector('[data-plus-button]');

                    if (!orderId) {
                        form.setAttribute('action', '#');
                        if (button) button.disabled = true;
                        if (quantityInput) quantityInput.disabled = true;
                        if (minusButton) minusButton.disabled = true;
                        if (plusButton) plusButton.disabled = true;
                        return;
                    }

                    form.setAttribute('action', actionTemplate.replace('__ORDER_ID__', orderId));
                    if (button) button.disabled = false;
                    if (quantityInput) quantityInput.disabled = false;
                    if (minusButton) minusButton.disabled = false;
                    if (plusButton) plusButton.disabled = false;
                });
            };

            const setupQuantityButtons = () => {
                document.querySelectorAll('form[data-menu-item-form]').forEach((form) => {
                    const qtyInput = form.querySelector('[data-quantity-input]');
                    const minusButton = form.querySelector('[data-minus-button]');
                    const plusButton = form.querySelector('[data-plus-button]');

                    if (!qtyInput) return;

                    minusButton?.addEventListener('click', () => {
                        const current = Math.max(1, parseInt(qtyInput.value || '1', 10));
                        qtyInput.value = Math.max(1, current - 1);
                    });

                    plusButton?.addEventListener('click', () => {
                        const current = Math.max(1, parseInt(qtyInput.value || '1', 10));
                        qtyInput.value = current + 1;
                    });
                });
            };

            syncMenuItemForms();
            setupQuantityButtons();

            searchInput?.addEventListener('input', () => {
                clearTimeout(filterDebounce);
                filterDebounce = setTimeout(() => filterForm?.submit(), 350);
            });

            typeSelect?.addEventListener('change', () => {
                filterForm?.submit();
            });

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
