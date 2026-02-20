<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-4">Buyurtmalar tarixi</h1>

            <form method="GET" class="bg-white rounded-xl border p-4 mb-4 grid grid-cols-1 md:grid-cols-6 gap-3">
                <select name="room_id" class="border rounded p-2">
                    <option value="">Barcha xonalar</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected(($filters['room_id'] ?? '') == $room->id)>{{ $room->number }}</option>
                    @endforeach
                </select>

                <select name="status" class="border rounded p-2">
                    <option value="">Barcha holatlar</option>
                    <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Yopilgan</option>
                    <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Bekor qilingan</option>
                </select>

                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="border rounded p-2">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="border rounded p-2">
                <button class="bg-slate-900 text-white rounded p-2">Filtrlash</button>
                <a href="{{ route('orders.history') }}" class="inline-flex items-center justify-center rounded border border-slate-300 bg-white p-2 text-slate-700 hover:bg-slate-50">
                    Tozalash
                </a>
            </form>

            <div class="bg-white rounded-xl border overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                    <tr>
                        <th class="text-left p-3">Buyurtma</th>
                        <th class="text-left p-3">Xona</th>
                        <th class="text-left p-3">Holat</th>
                        <th class="text-left p-3">Jami</th>
                        <th class="text-left p-3">Kassir</th>
                        <th class="text-left p-3">Yopilgan vaqt</th>
                        <th class="text-left p-3">Amal</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($orders as $order)
                        <tr class="border-t">
                            <td class="p-3">{{ $order->order_number }}</td>
                            <td class="p-3">{{ $order->room?->number }}</td>
                            <td class="p-3">
                                {{
                                    match ($order->status) {
                                        'closed' => 'Yopilgan',
                                        'cancelled' => 'Bekor qilingan',
                                        default => $order->status,
                                    }
                                }}
                            </td>
                            <td class="p-3">{{ number_format((float) $order->total_amount, 2) }}</td>
                            <td class="p-3">{{ $order->user?->name ?? '-' }}</td>
                            <td class="p-3">{{ $order->closed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="p-3"><a class="text-blue-700 underline" href="{{ route('orders.show', $order) }}">Ko'rish</a></td>
                        </tr>
                    @empty
                        <tr class="border-t"><td colspan="7" class="p-3">Tarix topilmadi.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
