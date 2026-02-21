<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chek tasdiqlash</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-xl px-4 py-10 sm:px-6">
        <div class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold">Chek tasdiqlandi</h1>
            <p class="mt-2 text-sm text-slate-600">Ushbu QR kod orqali tekshirilgan chek ma'lumotlari:</p>

            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-3 border-b pb-2">
                    <dt class="text-slate-600">Chek raqami</dt>
                    <dd class="font-semibold">{{ $bill->bill_number }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 border-b pb-2">
                    <dt class="text-slate-600">Buyurtma</dt>
                    <dd class="font-semibold">{{ $bill->order->order_number }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 border-b pb-2">
                    <dt class="text-slate-600">Xona</dt>
                    <dd class="font-semibold">{{ $bill->room->number }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 border-b pb-2">
                    <dt class="text-slate-600">To'langan summa</dt>
                    <dd class="font-semibold">{{ number_format((float) $bill->total_amount, 2) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-600">Holat</dt>
                    <dd class="font-semibold {{ $bill->is_printed ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $bill->is_printed ? 'Yakunlangan' : 'Yaratilgan' }}
                    </dd>
                </div>
            </dl>
        </div>
    </main>
</body>
</html>
