<x-mail::message>
# Envío de Factura

Cliente: {{ $factura->contact_name }},

Adjunto a este correo electrónico encontrará la factura **{{ $factura->number ?? 'borrador' }}**.

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
