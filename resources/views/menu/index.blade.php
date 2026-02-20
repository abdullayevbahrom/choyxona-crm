<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-4">Menyu boshqaruvi</h1>

            <form method="GET" class="bg-white rounded-xl border p-4 mb-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Qidirish" class="border rounded p-2">
                <select name="type" class="border rounded p-2">
                    <option value="">Barchasi</option>
                    @foreach (['food' => 'Taom', 'drink' => 'Ichimlik', 'bread' => 'Non', 'salad' => 'Salat', 'sauce' => 'Sous'] as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="bg-slate-900 text-white rounded p-2">Filter</button>
            </form>

            <form method="POST" action="{{ route('menu.store') }}" class="bg-white rounded-xl border p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <input name="name" placeholder="Nomi" class="border rounded p-2" required>
                <select name="type" class="border rounded p-2" required>
                    @foreach (['food', 'drink', 'bread', 'salad', 'sauce'] as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
                <input name="price" type="number" step="0.01" min="0" placeholder="Narx" class="border rounded p-2">
                <button class="bg-green-700 text-white rounded p-2">Qo'shish</button>
                <input name="stock_quantity" type="number" min="0" placeholder="Miqdor" class="border rounded p-2">
                <input name="unit" placeholder="Birlik" class="border rounded p-2">
                <textarea name="description" placeholder="Izoh" class="border rounded p-2 md:col-span-2"></textarea>
            </form>

            <div class="bg-white rounded-xl border overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                    <tr>
                        <th class="text-left p-3">Nomi</th>
                        <th class="text-left p-3">Tur</th>
                        <th class="text-left p-3">Narx</th>
                        <th class="text-left p-3">Miqdor</th>
                        <th class="text-left p-3">Birlik</th>
                        <th class="text-left p-3">Faol</th>
                        <th class="text-left p-3">Amal</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($items as $item)
                        <tr class="border-t">
                            <td class="p-3">
                                <form method="POST" action="{{ route('menu.update', $item) }}" class="grid grid-cols-1 gap-2 min-w-56">
                                    @csrf
                                    @method('PATCH')
                                    <input name="name" value="{{ $item->name }}" class="border rounded p-2" required>
                            </td>
                            <td class="p-3">
                                    <select name="type" class="border rounded p-2" required>
                                        @foreach (['food', 'drink', 'bread', 'salad', 'sauce'] as $type)
                                            <option value="{{ $type }}" @selected($item->type === $type)>{{ $type }}</option>
                                        @endforeach
                                    </select>
                            </td>
                            <td class="p-3">
                                    <input name="price" type="number" step="0.01" min="0" value="{{ $item->price }}" class="border rounded p-2" placeholder="Narx">
                            </td>
                            <td class="p-3">
                                    <input name="stock_quantity" type="number" min="0" value="{{ $item->stock_quantity }}" class="border rounded p-2" placeholder="Miqdor">
                            </td>
                            <td class="p-3">
                                    <input name="unit" value="{{ $item->unit }}" class="border rounded p-2" placeholder="Birlik">
                                    <textarea
                                        name="description"
                                        rows="2"
                                        class="mt-2 w-full border rounded p-2"
                                        placeholder="Izoh"
                                    >{{ $item->description }}</textarea>
                            </td>
                            <td class="p-3">{{ $item->is_active ? 'ha' : 'yo\'q' }}</td>
                            <td class="p-3 align-top">
                                    <button class="bg-blue-700 text-white rounded px-3 py-2 text-xs mb-2 w-full">Saqlash</button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('menu.toggle-active', $item) }}"
                                    data-confirm="Mahsulot faolligini almashtirmoqchimisiz?"
                                    data-disable-on-submit
                                    data-pending-text="Saqlanmoqda..."
                                >
                                    @csrf
                                    <button class="text-blue-700 underline text-xs">Faollikni almashtirish</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $items->links() }}</div>
        </div>
    </div>
</x-app-layout>
