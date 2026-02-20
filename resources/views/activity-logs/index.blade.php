<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-4">Activity Log</h1>

            @if($quickActions->isNotEmpty())
                <div class="mb-4 rounded-xl border bg-white p-3">
                    <p class="mb-2 text-sm font-semibold text-slate-700">Tezkor action filter</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($quickActions as $action)
                            <a
                                href="{{ route('activity-logs.index', array_merge(request()->query(), ['action' => $action])) }}"
                                class="rounded-full border px-3 py-1 text-xs {{ ($filters['action'] ?? '') === $action ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-100 text-slate-700 border-slate-300' }}"
                            >
                                {{ $action }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="GET" class="bg-white rounded-xl border p-4 mb-4 grid grid-cols-1 md:grid-cols-5 gap-3">
                <input name="action" value="{{ $filters['action'] ?? '' }}" placeholder="Action" class="border rounded p-2">

                <select name="user_id" class="border rounded p-2">
                    <option value="">Barcha userlar</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>

                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="border rounded p-2">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="border rounded p-2">

                <select name="subject_type" class="border rounded p-2 md:col-span-2">
                    <option value="">Barcha subject type</option>
                    @foreach ($subjectTypes as $subjectType)
                        <option value="{{ $subjectType }}" @selected(($filters['subject_type'] ?? '') === $subjectType)>{{ class_basename($subjectType) }}</option>
                    @endforeach
                </select>

                <input name="subject_id" type="number" min="1" value="{{ $filters['subject_id'] ?? '' }}" placeholder="Subject ID" class="border rounded p-2">

                <div class="flex gap-2 md:col-span-2">
                    <button class="bg-slate-900 text-white rounded p-2 flex-1">Filter</button>
                    <a href="{{ route('activity-logs.export', request()->query()) }}" class="bg-emerald-700 text-white rounded p-2 text-center flex-1">CSV Export</a>
                </div>
            </form>

            <div class="mb-4 rounded-xl border bg-white p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('activity-logs.exports.request') }}" class="flex items-center gap-2">
                        @csrf
                        @foreach(request()->query() as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ is_array($value) ? json_encode($value) : $value }}">
                        @endforeach
                        <button class="rounded bg-indigo-700 px-3 py-2 text-white text-sm">Background Export</button>
                    </form>
                    <span class="text-xs text-slate-600">Queue worker ishlayotgan bo'lsa export avtomatik tayyor bo'ladi.</span>
                </div>

                @if($exports->isNotEmpty())
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="p-2 text-left">ID</th>
                                    <th class="p-2 text-left">Status</th>
                                    <th class="p-2 text-left">Yaratilgan</th>
                                    <th class="p-2 text-left">Tugatildi</th>
                                    <th class="p-2 text-left">Fayl</th>
                                    <th class="p-2 text-left">Amal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exports as $export)
                                    <tr class="border-t">
                                        <td class="p-2">#{{ $export->id }}</td>
                                        <td class="p-2">{{ $export->status }}</td>
                                        <td class="p-2">{{ $export->created_at?->format('Y-m-d H:i:s') }}</td>
                                        <td class="p-2">{{ $export->finished_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                                        <td class="p-2">{{ $export->file_size ? number_format($export->file_size) . ' bytes' : '-' }}</td>
                                        <td class="p-2">
                                            @if($export->status === \App\Models\ActivityLogExport::STATUS_READY)
                                                <a href="{{ route('activity-logs.exports.download', $export) }}" class="text-blue-700 underline">Yuklash</a>
                                            @elseif($export->status === \App\Models\ActivityLogExport::STATUS_FAILED)
                                                <span class="text-red-700">{{ $export->error_message ?? 'Xato' }}</span>
                                            @else
                                                <span class="text-slate-600">Kutilmoqda...</span>
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
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="p-3 text-left">Vaqt</th>
                            <th class="p-3 text-left">User</th>
                            <th class="p-3 text-left">Action</th>
                            <th class="p-3 text-left">Subject</th>
                            <th class="p-3 text-left">Description</th>
                            <th class="p-3 text-left">Properties</th>
                            <th class="p-3 text-left">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr class="border-t align-top">
                                <td class="p-3">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="p-3">{{ $log->user?->name ?? '-' }}</td>
                                <td class="p-3">{{ $log->action }}</td>
                                <td class="p-3">
                                    @if($log->subject_url)
                                        <a href="{{ $log->subject_url }}" class="text-blue-700 underline">{{ class_basename($log->subject_type ?? '-') }} #{{ $log->subject_id ?? '-' }}</a>
                                    @else
                                        {{ class_basename($log->subject_type ?? '-') }} #{{ $log->subject_id ?? '-' }}
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
</x-app-layout>
