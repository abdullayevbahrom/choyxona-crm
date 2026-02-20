<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <h1 class="text-2xl font-bold">Hisobotlar</h1>

            <form method="GET" class="bg-white rounded-xl border p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="border rounded p-2">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="border rounded p-2">

                <select name="room_id" class="border rounded p-2">
                    <option value="">Barcha xonalar</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected(($filters['room_id'] ?? '') == $room->id)>{{ $room->number }}</option>
                    @endforeach
                </select>

                <select name="cashier_id" class="border rounded p-2">
                    <option value="">Barcha kassirlar</option>
                    @foreach ($cashiers as $cashier)
                        <option value="{{ $cashier->id }}" @selected(($filters['cashier_id'] ?? '') == $cashier->id)>{{ $cashier->name }}</option>
                    @endforeach
                </select>

                <button class="bg-slate-900 text-white rounded p-2">Filter</button>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl border p-5">
                    <p class="text-sm text-slate-500">Jami daromad</p>
                    <p class="text-3xl font-bold">{{ number_format($totalRevenue, 2) }}</p>
                </div>
                <div class="bg-white rounded-xl border p-5">
                    <p class="text-sm text-slate-500">Yopilgan buyurtmalar</p>
                    <p class="text-3xl font-bold">{{ $ordersCount }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl border overflow-x-auto">
                    <h2 class="font-semibold p-4 border-b">Kunlik daromad (so'nggi 31 kun)</h2>
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr><th class="p-3 text-left">Sana</th><th class="p-3 text-left">Daromad</th></tr>
                        </thead>
                        <tbody>
                        @forelse($dailyRevenue as $row)
                            <tr class="border-t"><td class="p-3">{{ $row->day }}</td><td class="p-3">{{ number_format((float) $row->revenue, 2) }}</td></tr>
                        @empty
                            <tr class="border-t"><td colspan="2" class="p-3">Ma'lumot yo'q.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-white rounded-xl border overflow-x-auto">
                    <h2 class="font-semibold p-4 border-b">Oylik daromad (12 oy)</h2>
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr><th class="p-3 text-left">Oy</th><th class="p-3 text-left">Daromad</th></tr>
                        </thead>
                        <tbody>
                        @forelse($monthlyRevenue as $row)
                            <tr class="border-t"><td class="p-3">{{ $row->ym }}</td><td class="p-3">{{ number_format((float) $row->revenue, 2) }}</td></tr>
                        @empty
                            <tr class="border-t"><td colspan="2" class="p-3">Ma'lumot yo'q.</td></tr>
                        @endforelse
                        </tbody>
                    </table>

                    @php
                        $monthlyChart = collect($monthlyRevenue)->reverse()->values();
                        $maxRevenue = max(1, (float) $monthlyChart->max('revenue'));
                    @endphp
                    <div class="border-t p-4">
                        <h3 class="mb-3 text-sm font-semibold text-slate-700">Oylik grafik</h3>
                        <div class="flex h-40 items-end gap-2">
                            @forelse ($monthlyChart as $row)
                                @php
                                    $value = (float) $row->revenue;
                                    $heightPercent = max(3, (int) round(($value / $maxRevenue) * 100));
                                @endphp
                                <div class="group relative flex-1">
                                    <div
                                        class="w-full rounded-t bg-emerald-500 transition group-hover:bg-emerald-600"
                                        style="height: {{ $heightPercent }}%; min-height: 8px;"
                                        title="{{ $row->ym }}: {{ number_format($value, 2) }}"
                                    ></div>
                                    <div class="mt-1 text-center text-[10px] text-slate-600">{{ $row->ym }}</div>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500">Grafik uchun ma'lumot yo'q.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border overflow-x-auto">
                    <h2 class="font-semibold p-4 border-b">TOP-10 mahsulot</h2>
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr><th class="p-3 text-left">Nomi</th><th class="p-3 text-left">Soni</th><th class="p-3 text-left">Daromad</th></tr>
                        </thead>
                        <tbody>
                        @forelse($topItems as $row)
                            <tr class="border-t"><td class="p-3">{{ $row->item_name }}</td><td class="p-3">{{ $row->total_qty }}</td><td class="p-3">{{ number_format((float) $row->revenue, 2) }}</td></tr>
                        @empty
                            <tr class="border-t"><td colspan="3" class="p-3">Ma'lumot yo'q.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-white rounded-xl border overflow-x-auto">
                    <h2 class="font-semibold p-4 border-b">Xonalar faolligi</h2>
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr><th class="p-3 text-left">Xona</th><th class="p-3 text-left">Buyurtma</th><th class="p-3 text-left">Daromad</th></tr>
                        </thead>
                        <tbody>
                        @forelse($roomStats as $row)
                            <tr class="border-t"><td class="p-3">{{ $row->room_number }}</td><td class="p-3">{{ $row->orders_count }}</td><td class="p-3">{{ number_format((float) $row->revenue, 2) }}</td></tr>
                        @empty
                            <tr class="border-t"><td colspan="3" class="p-3">Ma'lumot yo'q.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-white rounded-xl border overflow-x-auto">
                    <h2 class="font-semibold p-4 border-b">Kassirlar statistikasi</h2>
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr><th class="p-3 text-left">Kassir</th><th class="p-3 text-left">Chek</th><th class="p-3 text-left">Daromad</th></tr>
                        </thead>
                        <tbody>
                        @forelse($cashierStats as $row)
                            <tr class="border-t"><td class="p-3">{{ $row->cashier_name }}</td><td class="p-3">{{ $row->bills_count }}</td><td class="p-3">{{ number_format((float) $row->revenue, 2) }}</td></tr>
                        @empty
                            <tr class="border-t"><td colspan="3" class="p-3">Ma'lumot yo'q.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
