@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (file_exists(public_path('img/logo.png')))
<img src="{{ config('app.url') }}/img/logo.png" class="logo" alt="{{ config('app.name') }}" style="width: auto; max-width: 280px; height: auto; max-height: 60px;">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
