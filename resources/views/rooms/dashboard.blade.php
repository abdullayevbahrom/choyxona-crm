<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-2xl font-bold">Xonalar Paneli</h1>
                <span class="text-sm text-slate-600">30 soniyada avtomatik yangilanadi</span>
            </div>

            <div id="room-cards" data-url="{{ route('dashboard.cards') }}">
                @include('rooms.partials.cards', ['rooms' => $rooms])
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.setupHtmlPolling?.({
                containerId: 'room-cards',
                url: @js(route('dashboard.cards')),
                fingerprintUrl: @js(route('dashboard.fingerprint')),
                intervalMs: 30000,
            });
        });
    </script>
</x-app-layout>
