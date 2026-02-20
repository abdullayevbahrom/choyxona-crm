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

                <div>
                    <button class="w-full rounded bg-slate-900 px-4 py-2 text-white sm:w-auto">Saqlash</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
