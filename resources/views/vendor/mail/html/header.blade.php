@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@php
    $logoFile = '/var/www/vhosts/TU_DOMINIO/httpdocs/public/img/logo.png';
    $logoBase64 = '';
    if (file_exists($logoFile)) {
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
        $logoBase64 = str_replace(["\r", "\n", " "], '', $logoBase64);
        \Illuminate\Support\Facades\Log::info('Email Header: Logo detected and encoded.');
    } else {
        \Illuminate\Support\Facades\Log::warning('Email Header: Logo NOT found at ' . $logoFile);
    }
@endphp
@if ($logoBase64)
<img src="{{ $logoBase64 }}" class="logo" alt="{{ config('app.name') }}" style="width: auto; max-width: 250px; height: auto; max-height: 50px; border: none; display: block;">
@else
{{ config('app.name') }}
@endif
</a>
</td>
</tr>
