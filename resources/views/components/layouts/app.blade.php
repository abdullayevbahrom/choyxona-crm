<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <title>{{ $title ?? 'Choyxona CRM' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-amber-50 text-slate-800">
<nav class="bg-slate-900 text-white">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">
        <a href="{{ route('dashboard') }}" class="font-semibold">Asosiy panel</a>
        <a href="{{ route('menu.index') }}">Menyu</a>
        <a href="{{ route('rooms.index') }}">Xonalar</a>
    </div>
</nav>

<main class="max-w-7xl mx-auto p-4">
    @if (session('status'))
        @php
            $status = (string) session('status');
            $statusMessage = str_contains($status, '.') || str_contains($status, ' ')
                ? __($status)
                : __("status.{$status}");
        @endphp
        <div class="mb-4 rounded bg-green-100 border border-green-300 text-green-800 p-3">{{ $statusMessage }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded bg-red-100 border border-red-300 text-red-800 p-3">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{ $slot }}
</main>
</body>
</html>
