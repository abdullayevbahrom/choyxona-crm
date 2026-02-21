<x-app-layout>
    @php
        $exportStatusLabels = [
            \App\Models\ActivityLogExport::STATUS_PENDING => 'Kutilmoqda',
            \App\Models\ActivityLogExport::STATUS_PROCESSING => 'Jarayonda',
            \App\Models\ActivityLogExport::STATUS_READY => 'Tayyor',
            \App\Models\ActivityLogExport::STATUS_FAILED => 'Xato',
        ];
        $quickActionLabels = [
            'orders.create' => 'Buyurtma ochish',
            'orders.cancel' => 'Buyurtmani bekor qilish',
            'orders.items.add' => "Buyurtmaga mahsulot qo'shish",
            'orders.items.update' => 'Mahsulot miqdorini yangilash',
            'orders.items.remove' => 'Mahsulotni olib tashlash',
            'bills.create' => 'Chek yaratish',
            'bills.print' => 'Chek chop etish',
            'rooms.toggle_active' => 'Xona faolligini almashtirish',
            'rooms.create' => 'Xona yaratish',
            'rooms.update' => 'Xona yangilash',
            'menu.store' => "Menyu mahsuloti qo'shish",
            'menu.update' => 'Menyu mahsulotini yangilash',
            'menu.toggle_active' => 'Menyu faolligini almashtirish',
            'settings.update' => 'Sozlamalarni yangilash',
            'users.store' => "Foydalanuvchi qo'shish",
            'users.update' => 'Foydalanuvchini yangilash',
        ];
        $subjectTypeLabels = [
            \App\Models\Order::class => 'Buyurtma',
            \App\Models\Bill::class => 'Chek',
            \App\Models\Room::class => 'Xona',
            \App\Models\MenuItem::class => 'Menyu mahsuloti',
            \App\Models\Setting::class => 'Sozlama',
            \App\Models\User::class => 'Foydalanuvchi',
        ];
    @endphp
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-4">Faoliyat jurnali</h1>

            @if($quickActions->isNotEmpty())
                <div class="mb-4 rounded-xl border bg-white p-3">
                    <p class="mb-2 text-sm font-semibold text-slate-700">Tezkor amal filtrlari</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($quickActions as $action)
                            <a
                                href="{{ route('activity-logs.index', array_merge(request()->query(), ['action' => $action])) }}"
                                class="rounded-full border px-3 py-1 text-xs {{ ($filters['action'] ?? '') === $action ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-100 text-slate-700 border-slate-300' }}"
                            >
                                {{ $quickActionLabels[$action] ?? $action }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="GET" class="bg-white rounded-xl border p-4 mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <input name="action" value="{{ $filters['action'] ?? '' }}" placeholder="Amal" class="border rounded p-2">

                <select name="user_id" class="border rounded p-2">
                    <option value="">Barcha foydalanuvchilar</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>

                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="border rounded p-2">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="border rounded p-2">

                <select name="subject_type" class="border rounded p-2 sm:col-span-2 xl:col-span-2">
                    <option value="">Barcha obyekt turlari</option>
                    @foreach ($subjectTypes as $subjectType)
                        <option value="{{ $subjectType }}" @selected(($filters['subject_type'] ?? '') === $subjectType)>{{ $subjectTypeLabels[$subjectType] ?? class_basename($subjectType) }}</option>
                    @endforeach
                </select>

                <input name="subject_id" type="number" min="1" value="{{ $filters['subject_id'] ?? '' }}" placeholder="Obyekt ID" class="border rounded p-2">

                <div class="flex flex-col gap-2 sm:flex-row sm:col-span-2 xl:col-span-2">
                    <button class="bg-slate-900 text-white rounded p-2 flex-1">Filtrlash</button>
                    <a href="{{ route('activity-logs.export', request()->query()) }}" class="bg-emerald-700 text-white rounded p-2 text-center flex-1">CSV yuklab olish</a>
                </div>
            </form>

            <div class="mb-4 rounded-xl border bg-white p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('activity-logs.exports.request') }}" class="flex items-center gap-2">
                        @csrf
                        @foreach(request()->query() as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ is_array($value) ? json_encode($value) : $value }}">
                        @endforeach
                        <button class="rounded bg-indigo-700 px-3 py-2 text-white text-sm">Fon rejimida eksport</button>
                    </form>
                    <span class="text-xs text-slate-600">Navbat xizmati ishlayotgan bo'lsa eksport avtomatik tayyor bo'ladi.</span>
                </div>

                @if($exports->isNotEmpty())
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-[760px] w-full text-xs">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="p-2 text-left">ID</th>
                                    <th class="p-2 text-left">Holat</th>
                                    <th class="p-2 text-left">Yaratilgan</th>
                                    <th class="p-2 text-left">Tugatildi</th>
                                    <th class="p-2 text-left">Fayl</th>
                                    <th class="p-2 text-left">Amal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exports as $export)
                                    <tr class="border-t" data-export-row="{{ $export->id }}">
                                        <td class="p-2">#{{ $export->id }}</td>
                                        <td class="p-2" data-export-status data-export-status-code="{{ $export->status }}">{{ $exportStatusLabels[$export->status] ?? $export->status }}</td>
                                        <td class="p-2">{{ $export->created_at?->format('Y-m-d H:i:s') }}</td>
                                        <td class="p-2" data-export-finished>{{ $export->finished_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                                        <td class="p-2" data-export-file-size>{{ $export->file_size ? number_format($export->file_size) . ' bayt' : '-' }}</td>
                                        <td class="p-2">
                                            @if($export->status === \App\Models\ActivityLogExport::STATUS_READY)
                                                <a href="{{ route('activity-logs.exports.download', $export) }}" class="text-blue-700 underline" data-export-download>Yuklash</a>
                                                <span class="hidden text-slate-600" data-export-pending>Kutilmoqda...</span>
                                            @elseif($export->status === \App\Models\ActivityLogExport::STATUS_FAILED)
                                                <span class="text-red-700" data-export-failed>{{ $export->error_message ?? 'Xato' }}</span>
                                                <a href="#" class="hidden text-blue-700 underline" data-export-download>Yuklash</a>
                                                <span class="hidden text-slate-600" data-export-pending>Kutilmoqda...</span>
                                            @else
                                                <a href="#" class="hidden text-blue-700 underline" data-export-download>Yuklash</a>
                                                <span class="text-slate-600" data-export-pending>Kutilmoqda...</span>
                                                <span class="hidden text-red-700" data-export-failed>Xato</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl border overflow-x-auto">
                <table class="min-w-[980px] w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="p-3 text-left">Vaqt</th>
                            <th class="p-3 text-left">Foydalanuvchi</th>
                            <th class="p-3 text-left">Amal</th>
                            <th class="p-3 text-left">Obyekt</th>
                            <th class="p-3 text-left">Tavsif</th>
                            <th class="p-3 text-left">Qo'shimcha ma'lumot</th>
                            <th class="p-3 text-left">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr class="border-t align-top">
                                <td class="p-3">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="p-3">{{ $log->user?->name ?? '-' }}</td>
                                <td class="p-3">{{ $quickActionLabels[$log->action] ?? $log->action }}</td>
                                <td class="p-3">
                                    @if($log->subject_url)
                                        <a href="{{ $log->subject_url }}" class="text-blue-700 underline">{{ $subjectTypeLabels[$log->subject_type] ?? class_basename($log->subject_type ?? '-') }} #{{ $log->subject_id ?? '-' }}</a>
                                    @else
                                        {{ $subjectTypeLabels[$log->subject_type] ?? class_basename($log->subject_type ?? '-') }} #{{ $log->subject_id ?? '-' }}
                                    @endif
                                </td>
                                <td class="p-3">{{ $log->description ?? '-' }}</td>
                                <td class="p-3">
                                    @if (!empty($log->properties))
                                        <details>
                                            <summary class="cursor-pointer text-blue-700">Ko'rish</summary>
                                            <pre class="mt-2 whitespace-pre-wrap text-xs text-slate-700">{{ json_encode($log->properties, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="p-3">{{ $log->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr class="border-t"><td colspan="7" class="p-3">Loglar topilmadi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $logs->links() }}</div>
        </div>
    </div>

    <script>
        (function () {
            let timerId = null;
            let inFlight = false;
            let statusesEtag = null;
            const statusLabels = {
                pending: 'Kutilmoqda',
                processing: 'Jarayonda',
                ready: 'Tayyor',
                failed: 'Xato',
            };

            const formatBytes = (value) => {
                const size = Number(value ?? 0);
                if (!Number.isFinite(size) || size <= 0) return '-';
                return `${size.toLocaleString('en-US')} bayt`;
            };
            const formatDateTime = (value) => {
                if (!value) return '-';
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) return '-';
                const pad = (num) => String(num).padStart(2, '0');
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
            };

            const poll = () => {
                if (document.hidden || inFlight) return;

                const rows = Array.from(document.querySelectorAll('[data-export-row]'));
                const pendingRows = rows.filter((row) => {
                    const statusNode = row.querySelector('[data-export-status]');
                    const code = statusNode?.getAttribute('data-export-status-code');
                    return statusNode && code !== 'ready' && code !== 'failed';
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

                inFlight = true;
                const params = new URLSearchParams();
                ids.forEach((id) => params.append('ids[]', id));

                const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                if (statusesEtag) {
                    headers['If-None-Match'] = statusesEtag;
                }

                fetch(`/activity-logs/exports/statuses?${params.toString()}`, { headers })
                    .then((response) => {
                        if (response.status === 304) {
                            return null;
                        }
                        if (!response.ok) {
                            return null;
                        }

                        const nextEtag = response.headers.get('etag');
                        if (nextEtag) {
                            statusesEtag = nextEtag;
                        }

                        return response.json();
                    })
                    .then((payload) => {
                        if (!payload) return;
                        const exports = payload?.exports ?? [];

                        exports.forEach((item) => {
                            const row = document.querySelector(`[data-export-row="${item.id}"]`);
                            if (!row) return;

                            const statusNode = row.querySelector('[data-export-status]');
                            const finishedNode = row.querySelector('[data-export-finished]');
                            const fileSizeNode = row.querySelector('[data-export-file-size]');
                            const pendingNode = row.querySelector('[data-export-pending]');
                            const failedNode = row.querySelector('[data-export-failed]');
                            const downloadNode = row.querySelector('[data-export-download]');
                            if (!statusNode || !finishedNode || !fileSizeNode || !pendingNode || !downloadNode) return;

                            statusNode.setAttribute('data-export-status-code', item.status);
                            statusNode.textContent = statusLabels[item.status] ?? item.status;
                            finishedNode.textContent = formatDateTime(item.finished_at);
                            fileSizeNode.textContent = formatBytes(item.file_size);

                            if (item.status === 'ready') {
                                downloadNode.href = item.download_url ?? `/activity-logs/exports/${item.id}`;
                                downloadNode.classList.remove('hidden');
                                pendingNode.classList.add('hidden');
                                if (failedNode) failedNode.classList.add('hidden');
                            } else if (item.status === 'failed') {
                                pendingNode.classList.add('hidden');
                                downloadNode.classList.add('hidden');
                                if (failedNode) {
                                    failedNode.textContent = item.error_message ?? 'Xato';
                                    failedNode.classList.remove('hidden');
                                }
                            } else {
                                pendingNode.classList.remove('hidden');
                                downloadNode.classList.add('hidden');
                                if (failedNode) failedNode.classList.add('hidden');
                            }
                        });
                    })
                    .catch(() => {})
                    .finally(() => {
                        inFlight = false;
                    });
            };

            const start = () => {
                poll();
                if (timerId === null) {
                    timerId = setInterval(poll, 3000);
                }
            };

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    poll();
                }
            });

            start();
        })();
    </script>
</x-app-layout>
