<x-mail::message>
# Envío de Presupuesto

Cliente: {{ $presupuesto->contact_name }},

Adjunto a este correo electrónico encontrará el presupuesto **{{ $presupuesto->number }}**.

@if($customMessage)
---
{!! nl2br(e($customMessage)) !!}
---
@else
Quedamos a su entera disposición para cualquier duda o aclaración.
@endif

Atentamente,<br>
Francis Valenzuela
</x-mail::message>
