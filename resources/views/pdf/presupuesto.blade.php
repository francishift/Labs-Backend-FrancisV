<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Presupuesto: {{ $presupuesto->number }}</title>
    <style>
        @font-face {
            font-family: 'Lato';
            src: url('{{ public_path('fonts/Lato/Lato-Light.ttf') }}') format('truetype');
            font-weight: 300;
            font-style: normal;
        }
        @font-face {
            font-family: 'Lato';
            src: url('{{ public_path('fonts/Lato/Lato-Regular.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Lato';
            src: url('{{ public_path('fonts/Lato/Lato-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Lato';
            src: url('{{ public_path('fonts/Lato/Lato-Bold.ttf') }}') format('truetype');
            font-weight: 600;
            font-style: normal;
        }
        @font-face {
            font-family: 'Lato';
            src: url('{{ public_path('fonts/Lato/Lato-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @page {
            margin: 40px 50px 80px 50px;
        }
        body {
            font-family: 'Lato', sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #27272a;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 50px;
            width: 100%;
        }
        .header-title {
            font-size: 32pt;
            font-weight: 600;
            margin: 0;
            color: #18181b;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .meta-table {
            margin-top: 15px;
            font-size: 8.5pt;
        }
        .meta-table td {
            padding: 2px 10px 2px 0;
        }
        .meta-label {
            font-weight: bold;
            color: #18181b;
        }
        .meta-value {
            color: #52525b;
        }

        .details-container {
            width: 100%;
            margin-bottom: 40px;
        }
        .client-box {
            float: left;
            width: 50%;
        }
        .company-box {
            float: right;
            width: 40%;
            text-align: right;
        }
        .box-title {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 8px;
            color: #18181b;
        }
        .box-content {
            color: #52525b;
            line-height: 1.6;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.items-table th {
            border-bottom: 1px solid #18181b;
            padding: 10px 5px;
            font-size: 8.5pt;
            font-weight: bold;
            color: #18181b;
            text-align: left;
        }
        table.items-table td {
            padding: 15px 5px;
            border-bottom: 1px solid #e4e4e7;
            vertical-align: top;
            color: #3f3f46;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }

        .summary-box {
            background-color: #f4f4f5;
            padding: 30px 50px;
            margin-left: -50px;
            margin-right: -50px;
            page-break-inside: avoid;
        }
        .payment-info {
            float: left;
            width: 45%;
        }
        .payment-title {
            font-weight: bold;
            margin-bottom: 15px;
            color: #18181b;
        }
        .payment-details {
            font-size: 11pt;
            color: #3f3f46;
            margin-top: 40px;
        }
        
        .totals-table {
            float: right;
            width: 45%;
            font-size: 9.5pt;
        }
        .totals-table td {
            padding: 6px 0;
            color: #18181b;
        }
        .tot-val {
            text-align: right;
            color: #52525b;
        }
        .grand-total-row td {
            border-top: 1px solid #18181b;
            padding-top: 15px;
            margin-top: 10px;
        }
        .grand-total-val {
            font-size: 30pt;
            font-weight: 600;
            color: #18181b;
            text-align: right;
            letter-spacing: 1px;
        }

        .footer {
            position: fixed;
            bottom: -40px;
            left: 0px;
            right: 0px;
            height: 40px;
            border-top: 1px solid #e4e4e7;
            padding-top: 10px;
            font-size: 8pt;
            color: #71717a;
        }
        .page-number:after { content: counter(page); }
        .clear { clear: both; }

        .item-concept { font-weight: bold; color: #18181b; margin-bottom: 4px;}
        .item-desc { font-size: 8pt; color: #71717a; }

        .desc-content,
        .desc-content p, 
        .desc-content span, 
        .desc-content div, 
        .desc-content li, 
        .desc-content ul, 
        .desc-content ol,
        .desc-content table,
        .desc-content td,
        .desc-content th {
            color: #27272a !important; 
        }
        .desc-content p {
            margin-top: 4px;
            margin-bottom: 6px;
        }
        .desc-content ul, .desc-content ol {
            padding-left: 20px;
            margin-top: 4px;
            margin-bottom: 6px;
        }
        .desc-content li {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>

    @if($presupuesto->status === \App\Enums\PresupuestoStatus::CANCELED || (is_int($presupuesto->status) && $presupuesto->status === 2) || (isset($presupuesto->status->value) && $presupuesto->status->value === 2))
    <div style="position: absolute; top: 35%; left: 15%; font-size: 90pt; color: rgba(239, 68, 68, 0.15); transform: rotate(-45deg); z-index: -100; white-space: nowrap; font-weight: 800; text-transform: uppercase; border: 15px solid rgba(239, 68, 68, 0.15); padding: 20px; border-radius: 30px;">
        ANULADO
    </div>
    @elseif($presupuesto->status === \App\Enums\PresupuestoStatus::REJECTED || (is_int($presupuesto->status) && $presupuesto->status === 3) || (isset($presupuesto->status->value) && $presupuesto->status->value === 3))
    <div style="position: absolute; top: 35%; left: 5%; font-size: 90pt; color: rgba(239, 68, 68, 0.15); transform: rotate(-45deg); z-index: -100; white-space: nowrap; font-weight: 800; text-transform: uppercase; border: 15px solid rgba(239, 68, 68, 0.15); padding: 20px; border-radius: 30px;">
        RECHAZADO
    </div>
    @endif

    @php
        $strDate = is_numeric($presupuesto->date) ? $presupuesto->date : strtotime($presupuesto->date);
        $fecha = date('d/m/Y', $strDate);
        if ($presupuesto->due_date) {
            $strDueDate = is_numeric($presupuesto->due_date) ? $presupuesto->due_date : strtotime($presupuesto->due_date);
            $vencimiento = date('d/m/Y', $strDueDate);
        } else {
            $defaultDays = $configList['default_vencimiento_dias'] ?? 30;
            $vencimiento = date('d/m/Y', strtotime('+' . $defaultDays . ' days', $strDate));
        }
        
        $globalIvaPercent = "21%";
        $globalIrpfPercent = "15%";
        if($presupuesto->lineas->count() > 0) {
            $globalIvaPercent = number_format($presupuesto->lineas->first()->porcentaje_iva, 0) . '%';
            $globalIrpfPercent = number_format($presupuesto->lineas->first()->porcentaje_irpf, 0) . '%';
        }
    @endphp

    <div class="footer">
        <div style="float: right;">Pág. <span class="page-number"></span></div>
        <div>
            {{ $presupuesto->number }} - {{ number_format($presupuesto->total, 2, ',', '.') }}€ Vencimiento {{ $vencimiento }}<br>
            Datos bancarios para pagos:<br>
            {{ $configList['empresa_banco_nombre'] ?? 'Banco' }}: {{ $configList['empresa_banco_iban'] ?? 'ES00 0000 0000 0000 0000 0000' }}
        </div>
    </div>

    <div class="header">
        <div style="float: right; width: 180px; text-align: right;">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" style="max-width: 100%; max-height: 80px;">
            @endif
        </div>
        <div style="float: left; width: 50%;">
            <h1 class="header-title">PRESUPUESTO</h1>
            
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Número</td>
                    <td class="meta-value">{{ $presupuesto->number }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Fecha</td>
                    <td class="meta-value">{{ $fecha }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Vencimiento</td>
                    <td class="meta-value">{{ $vencimiento }}</td>
                </tr>
            </table>
        </div>
        <div class="clear"></div>
    </div>

    <div class="details-container">
        <div class="client-box">
            <div class="box-title">Cliente</div>
            <div class="box-content">
                @php
                    $resolvedClient = $presupuesto->cliente;
                    if (!$resolvedClient && $presupuesto->contact) {
                        $resolvedClient = \App\Models\Client::where('contact', $presupuesto->contact)
                            ->orWhereJsonContains('secondary_contacts', $presupuesto->contact)
                            ->first();
                    }
                @endphp

                {{ $presupuesto->contact_name }}<br>
                
                @if($resolvedClient)
                    @if($resolvedClient->address) {{ $resolvedClient->address }}<br>@endif
                    @if($resolvedClient->city || $resolvedClient->province || $resolvedClient->zip_code)
                        {{ $resolvedClient->city }} @if($resolvedClient->zip_code)({{ $resolvedClient->zip_code }})@endif, {{ $resolvedClient->province }}<br>
                    @endif
                    @if($resolvedClient->cif_nif) {{ $resolvedClient->cif_nif }}<br>@endif
                @elseif(isset($presupuesto->raw_data['contactAddress']) && !empty($presupuesto->raw_data['contactAddress']))
                    {{ $presupuesto->raw_data['contactAddress'] }}<br>
                    {{ $presupuesto->raw_data['contactCity'] ?? '' }} {{ $presupuesto->raw_data['contactZip'] ?? '' }}, {{ $presupuesto->raw_data['contactProvince'] ?? '' }}<br>
                    @if(!empty($presupuesto->raw_data['contactCif'])) {{ $presupuesto->raw_data['contactCif'] }}<br>@endif
                @endif
            </div>
        </div>
        <div class="company-box">
            <div class="box-title">{{ $configList['empresa_nombre'] ?? config('app.name') }}</div>
            <div class="box-content">
                @if(!empty($configList['empresa_nif'])) {{ $configList['empresa_nif'] }}<br> @endif
                @if(!empty($configList['empresa_direccion'])) {!! nl2br(e($configList['empresa_direccion'])) !!}<br> @endif
                @if(!empty($configList['empresa_email'])) {{ $configList['empresa_email'] }} @endif
                @if(!empty($configList['empresa_telefono'])) | {{ $configList['empresa_telefono'] }} @endif
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Concepto</th>
                <th style="width: 11%;" class="text-right">Precio</th>
                <th style="width: 10%;" class="text-center">Unidades</th>
                <th style="width: 11%;" class="text-right">Subtotal</th>
                <th style="width: 6%;" class="text-center">Iva</th>
                <th style="width: 8%;" class="text-center">Retención</th>
                <th style="width: 12%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($presupuesto->lineas as $linea)
            @php
                $subtotalLinea = $linea->cantidad * $linea->precio_unitario;
                $ivaLinea = $subtotalLinea * ($linea->porcentaje_iva / 100);
                $irpfLinea = $subtotalLinea * ($linea->porcentaje_irpf / 100);
                $totalFinalLinea = $subtotalLinea + $ivaLinea - $irpfLinea;
            @endphp
            <tr>
                <td>
                    <div class="item-concept">{{ $linea->concepto }}</div>
                    @if($linea->descripcion)
                        <div class="item-desc" style="white-space: pre-line;">{{ trim($linea->descripcion) }}</div>
                    @elseif(str_contains($linea->concepto, "\n"))
                        <div class="item-desc">{!! nl2br(e(explode("\n", $linea->concepto, 2)[1] ?? '')) !!}</div>
                    @endif
                </td>
                <td class="text-right">{{ number_format($linea->precio_unitario, 2, ',', '.') }}€</td>
                <td class="text-center">{{ number_format($linea->cantidad, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($subtotalLinea, 2, ',', '.') }}€</td>
                <td class="text-center">{{ number_format($linea->porcentaje_iva, 0) }}%</td>
                <td class="text-center">@if($linea->porcentaje_irpf > 0)-{{ number_format($linea->porcentaje_irpf, 0) }}%@endif</td>
                <td class="text-right">{{ number_format($totalFinalLinea, 2, ',', '.') }}€</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $hasDescription = $presupuesto->description && trim(strip_tags(str_replace(['&nbsp;', ' ', "\n", "\r"], '', $presupuesto->description))) !== '';
    @endphp

    <div class="summary-box" style="page-break-inside: avoid;">
        <div class="payment-info">
            @if($hasDescription)
                <div style="margin-bottom: 20px;">
                    <div class="payment-title" style="margin-bottom: 5px;">Observaciones</div>
                    <div class="desc-content" style="font-size: 8pt; color: #52525b; line-height: 1.4;">
                        {!! $presupuesto->description !!}
                    </div>
                </div>
            @endif

            <div class="payment-title">Condiciones de pago</div>
            @if($presupuesto->notes)
                <div style="font-size: 8.5pt; color: #52525b; margin-bottom: 5px;">
                    {!! nl2br(e($presupuesto->notes)) !!}
                </div>
            @endif
            <div class="payment-details">
                Transferencia bancaria {{ $configList['empresa_banco_nombre'] ?? 'Banco' }}<br>
                <strong>{{ $configList['empresa_banco_iban'] ?? 'ES00 0000 0000 0000 0000 0000' }}</strong>
            </div>
        </div>

        <table class="totals-table">
            <tr>
                <td>Base Imponible</td>
                <td class="tot-val">{{ number_format($presupuesto->subtotal, 2, ',', '.') }}€</td>
            </tr>
            @if($presupuesto->tax_amount > 0)
            <tr>
                <td>Iva {{ $globalIvaPercent }}</td>
                <td class="tot-val">{{ number_format($presupuesto->tax_amount, 2, ',', '.') }}€</td>
            </tr>
            @endif
            @if($presupuesto->irpf_amount > 0)
            <tr>
                <td>Retención {{ $globalIrpfPercent }}</td>
                <td class="tot-val">-{{ number_format($presupuesto->irpf_amount, 2, ',', '.') }}€</td>
            </tr>
            @endif
            <tr class="grand-total-row">
                <td style="font-size: 11pt; padding-top: 15px;">Total</td>
                <td class="grand-total-val" style="padding-top: 15px;">{{ number_format($presupuesto->total, 2, ',', '.') }}€</td>
            </tr>
        </table>
        <div class="clear"></div>
    </div>



</body>
</html>
