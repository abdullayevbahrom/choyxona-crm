<x-mail::message>
@if (!empty($logoUrl))
<p style="text-align:center; margin-bottom: 16px;">
    <img src="{{ $logoUrl }}" alt="{{ $brandName }}" width="64" height="64" style="display:inline-block; border-radius:9999px;">
</p>
@endif

Salom!

Siz ushbu xabarni hisobingiz uchun parolni tiklash so'rovi yuborilgani sababli oldingiz.

<x-mail::button :url="$resetUrl">
Parolni tiklash
</x-mail::button>

Ushbu parolni tiklash havolasi {{ $expire }} daqiqada tugaydi.

Agar parol tiklashni talab qilmagan bo'lsangiz, qo'shimcha harakat talab qilinmaydi.

Hurmat bilan,<br>
{{ $brandName }}

<x-slot:subcopy>
Agar "Parolni tiklash" tugmasi ishlamasa, quyidagi URL manzilni brauzerga qo'ying:
{{ $resetUrl }}
</x-slot:subcopy>
</x-mail::message>
