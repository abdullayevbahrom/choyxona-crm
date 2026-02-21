<x-app-layout>
    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-4">Sozlamalar</h1>

            <form method="POST" action="{{ route('settings.update') }}" class="bg-white rounded-xl border p-6 grid grid-cols-1 gap-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm font-medium mb-1">Choyxona nomi</label>
                    <input name="company_name" value="{{ old('company_name', $setting->company_name) }}" class="border rounded p-2 w-full" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Manzil</label>
                    <input name="company_address" value="{{ old('company_address', $setting->company_address) }}" class="border rounded p-2 w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Telefon</label>
                    <input name="company_phone" value="{{ old('company_phone', $setting->company_phone) }}" class="border rounded p-2 w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Chek footer matni</label>
                    <input name="receipt_footer" value="{{ old('receipt_footer', $setting->receipt_footer) }}" class="border rounded p-2 w-full">
                </div>

                <div class="border-t pt-4">
                    <p class="mb-3 text-sm font-semibold text-slate-700">Email xabarnoma sozlamalari</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Kimdan (nomi)</label>
                            <input name="notification_from_name" value="{{ old('notification_from_name', $setting->notification_from_name) }}" class="border rounded p-2 w-full" placeholder="Masalan: Choyxona CRM">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Kimdan (email)</label>
                            <input name="notification_from_email" type="email" value="{{ old('notification_from_email', $setting->notification_from_email) }}" class="border rounded p-2 w-full" placeholder="noreply@choyxona.uz">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-1">Email logo URL</label>
                        <input name="notification_logo_url" type="url" value="{{ old('notification_logo_url', $setting->notification_logo_url) }}" class="border rounded p-2 w-full" placeholder="https://example.com/logo.png">
                        <p class="mt-1 text-xs text-slate-500">Bo'sh qoldirilsa tizim logo rasmi ishlatiladi.</p>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-1">QR logo o'lchami (px)</label>
                        <input
                            name="notification_logo_size"
                            type="number"
                            min="16"
                            max="48"
                            value="{{ old('notification_logo_size', $setting->notification_logo_size) }}"
                            class="border rounded p-2 w-full sm:max-w-xs"
                            placeholder="24"
                        >
                        <p class="mt-1 text-xs text-slate-500">16 dan 48 gacha. Bo'sh bo'lsa default o'lcham ishlatiladi.</p>
                    </div>
                </div>

                <div>
                    <button class="w-full rounded bg-slate-900 px-4 py-2 text-white sm:w-auto">Saqlash</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
