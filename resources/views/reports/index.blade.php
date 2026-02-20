<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-bold">Hisobotlar</h1>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('reports.export.csv', request()->query()) }}" class="inline-flex items-center rounded border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        CSV export
                    </a>
                    <a href="{{ route('reports.export.xls', request()->query()) }}" class="inline-flex items-center rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                        XLSX export
                    </a>
                    <a href="{{ route('reports.export.pdf', request()->query()) }}" class="inline-flex items-center rounded border border-rose-300 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">
                        PDF export
                    </a>
                    <form method="POST" action="{{ route('reports.exports.request', request()->query()) }}">
                        @csrf
                        <button class="inline-flex items-center rounded border border-blue-300 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                            Background CSV
                        </button>
                    </form>
                </div>
            </div>

            @if ($errors->has('export'))
                <div class="rounded border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('export') }}
                </div>
            @endif

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

            <div class="bg-white rounded-xl border overflow-x-auto">
                <h2 class="font-semibold p-4 border-b">Background exportlar</h2>
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="p-3 text-left">ID</th>
                            <th class="p-3 text-left">Holat</th>
                            <th class="p-3 text-left">Format</th>
                            <th class="p-3 text-left">Yaratilgan</th>
                            <th class="p-3 text-left">Yuklab olish</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($exports as $export)
                        <tr class="border-t" data-export-row="{{ $export->id }}">
                            <td class="p-3">{{ $export->id }}</td>
                            <td class="p-3" data-export-status>{{ $export->status }}</td>
                            <td class="p-3">{{ strtoupper($export->format) }}</td>
                            <td class="p-3">{{ $export->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="p-3">
                                <a
                                    href="{{ route('reports.exports.download', $export) }}"
                                    class="text-blue-700 underline {{ $export->status === 'ready' ? '' : 'hidden' }}"
                                    data-export-download
                                >Yuklab olish</a>
                                <span class="text-slate-500 {{ $export->status === 'ready' ? 'hidden' : '' }}" data-export-pending>Tayyor emas</span>
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t"><td colspan="5" class="p-3">Exportlar yo'q.</td></tr>
                    @endforelse
                    </tbody>
                </table>
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

    <script>
        (function () {
            let timerId = null;

            const poll = () => {
                if (document.hidden) return;

                const rows = Array.from(document.querySelectorAll('[data-export-row]'));
                const pendingRows = rows.filter((row) => {
                    const statusNode = row.querySelector('[data-export-status]');
                    return statusNode && statusNode.textContent.trim() !== 'ready' && statusNode.textContent.trim() !== 'failed';
                });

                if (!pendingRows.length) {
                    if (timerId !== null) {
                        clearInterval(timerId);
                        timerId = null;
                    }
                    return;
                }

                const ids = pendingRows
                    .map((row) => row.getAttribute('data-export-row'))
                    .filter(Boolean);

                if (!ids.length) return;

                const params = new URLSearchParams();
                ids.forEach((id) => params.append('ids[]', id));

                fetch(`/reports/exports/statuses?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then((response) => response.ok ? response.json() : null)
                    .then((payload) => {
                        const exports = payload?.exports ?? [];

                        exports.forEach((item) => {
                            const row = document.querySelector(`[data-export-row="${item.id}"]`);
                            if (!row) return;

                            const statusNode = row.querySelector('[data-export-status]');
                            const pendingNode = row.querySelector('[data-export-pending]');
                            const downloadNode = row.querySelector('[data-export-download]');
                            if (!statusNode || !pendingNode || !downloadNode) return;

                            statusNode.textContent = item.status;

                            if (item.status === 'ready' && item.download_url) {
                                downloadNode.href = item.download_url;
                                downloadNode.classList.remove('hidden');
                                pendingNode.classList.add('hidden');
                            }
                        });
                    })
                    .catch(() => {});
            };

            poll();
            timerId = setInterval(poll, 5000);
        })();
    </script>
</x-app-layout>
