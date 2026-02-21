@props(['status'])

@if ($status)
    @php
        $status = (string) $status;
        $statusMessage = str_contains($status, '.') || str_contains($status, ' ')
            ? __($status)
            : __("status.{$status}");
    @endphp
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}>
        {{ $statusMessage }}
    </div>
@endif
