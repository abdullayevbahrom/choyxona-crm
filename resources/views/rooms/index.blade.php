<x-app-layout>
    @php
        $roomStatusLabels = [
            'empty' => "Bo'sh",
            'occupied' => 'Band',
        ];
    @endphp
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-4">Xonalar boshqaruvi</h1>

            <form method="GET" class="bg-white rounded-xl border p-4 mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-8">
                <input name="number" value="{{ $filters['number'] ?? '' }}" placeholder="Raqam" class="border rounded p-2">
                <input name="name" value="{{ $filters['name'] ?? '' }}" placeholder="Nomi" class="border rounded p-2">
                <input name="capacity" type="number" min="1" value="{{ $filters['capacity'] ?? '' }}" placeholder="Sig'im" class="border rounded p-2">
                <select name="status" class="border rounded p-2">
                    <option value="">Barcha holatlar</option>
                    <option value="empty" @selected(($filters['status'] ?? '') === 'empty')>Bo'sh</option>
                    <option value="occupied" @selected(($filters['status'] ?? '') === 'occupied')>Band</option>
                </select>
                <select name="is_active" class="border rounded p-2">
                    <option value="">Barcha faollik</option>
                    <option value="1" @selected((string) ($filters['is_active'] ?? '') === '1')>Faol</option>
                    <option value="0" @selected((string) ($filters['is_active'] ?? '') === '0')>Nofaol</option>
                </select>
                <select name="per_page" class="border rounded p-2">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected((int) ($filters['per_page'] ?? config('pagination.default_per_page', 10)) === (int) $option)>
                            {{ $option }} ta
                        </option>
                    @endforeach
                </select>
                <button class="bg-slate-900 text-white rounded p-2">Qo'llash</button>
                <a href="{{ route('rooms.index') }}" class="inline-flex items-center justify-center rounded border border-slate-300 bg-white p-2 text-slate-700 hover:bg-slate-50">Tozalash</a>
            </form>

            <form method="POST" action="{{ route('rooms.store') }}" class="bg-white rounded-xl border p-4 mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @csrf
                <input name="number" placeholder="Xona raqami" class="border rounded p-2" required>
                <input name="name" placeholder="Nomi" class="border rounded p-2">
                <input name="capacity" type="number" min="1" placeholder="Sig'im" class="border rounded p-2">
                <button class="bg-slate-900 text-white rounded p-2">Qo'shish</button>
                <textarea name="description" placeholder="Izoh" class="border rounded p-2 sm:col-span-2 xl:col-span-4"></textarea>
            </form>

            <div class="bg-white rounded-xl border overflow-x-auto">
                <table class="min-w-[780px] w-full text-sm">
                    <thead class="bg-slate-100">
                    <tr>
                        <th class="text-left p-3">Raqam</th>
                        <th class="text-left p-3">Nomi</th>
                        <th class="text-left p-3">Sig'im</th>
                        <th class="text-left p-3">Izoh</th>
                        <th class="text-left p-3">Holati</th>
                        <th class="text-left p-3">Faol</th>
                        <th class="text-left p-3">Amal</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rooms as $room)
                        <tr class="border-t">
                            <td class="p-3">
                                <form method="POST" action="{{ route('rooms.update', $room) }}" class="grid grid-cols-1 gap-2 min-w-0">
                                    @csrf
                                    @method('PATCH')
                                    <input name="number" value="{{ $room->number }}" class="w-full border rounded p-2" required>
                            </td>
                            <td class="p-3">
                                    <input name="name" value="{{ $room->name }}" class="w-full border rounded p-2" placeholder="Nomi">
                            </td>
                            <td class="p-3">
                                    <input name="capacity" type="number" min="1" value="{{ $room->capacity }}" class="w-24 border rounded p-2" placeholder="Sig'im">
                            </td>
                            <td class="p-3">
                                    <input name="description" value="{{ $room->description }}" class="w-full border rounded p-2" placeholder="Izoh">
                            </td>
                            <td class="p-3">{{ $roomStatusLabels[$room->status] ?? $room->status }}</td>
                            <td class="p-3">{{ $room->is_active ? 'ha' : 'yo\'q' }}</td>
                            <td class="w-[9rem] p-3 align-top">
                                    <button class="bg-blue-700 text-white rounded px-3 py-2 text-xs mb-2 w-full">Saqlash</button>
                                </form>

                                <form method="POST" action="{{ route('rooms.toggle-active', $room) }}">
                                    @csrf
                                    <button class="text-blue-700 underline text-xs">Faollikni almashtirish</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $rooms->links() }}</div>
        </div>
    </div>
</x-app-layout>
