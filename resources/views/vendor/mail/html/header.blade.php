@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
    <img src="{{ config('app.main_website_url') }}/images/logo.png" class="logo" alt="{{ config('app.name') }}" style="width:auto; max-width:250px; height:auto; max-height:50px; border:none; display:block;" />
</a>
</td>
</tr>
