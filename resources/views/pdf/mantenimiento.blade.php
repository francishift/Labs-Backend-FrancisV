<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Mantenimiento - {{ $mantenimiento->aplicacion }}</title>
    <style>
        @font-face {
            font-family: 'Lexend';
            src: url('{{ public_path('fonts/lexend/Lexend-Light.ttf') }}') format('truetype');
            font-weight: 300;
            font-style: normal;
        }
        @font-face {
            font-family: 'Lexend';
            src: url('{{ public_path('fonts/lexend/Lexend-Light.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Lexend';
            src: url('{{ public_path('fonts/lexend/Lexend-Light.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Lexend';
            src: url('{{ public_path('fonts/lexend/Lexend-Regular.ttf') }}') format('truetype');
            font-weight: 600;
            font-style: normal;
        }
        @font-face {
            font-family: 'Lexend';
            src: url('{{ public_path('fonts/lexend/Lexend-Regular.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Lexend', sans-serif;
            color: #18181b;
            line-height: 1.4;
            font-size: 10pt;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 20px;
        }
        .header-title {
            font-size: 24pt;
            font-weight: bold;
            margin: 0;
            color: #18181b;
        }
        .header-subtitle {
            font-size: 10pt;
            color: #71717a;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-badge {
            float: right;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            background-color: {{ $mantenimiento->estado === 'en curso' ? '#ecfdf5' : '#f4f4f5' }};
            color: {{ $mantenimiento->estado === 'en curso' ? '#065f46' : '#52525b' }};
        }
        .clear { clear: both; }

        .card {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .bg-zinc { background-color: #f4f4f5; border: 1px solid #e4e4e7; }
        .bg-emerald { background-color: #ecfdf5; border: 1px solid #d1fae5; }

        .label {
            font-size: 8pt;
            font-weight: bold;
            color: #71717a;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: block;
        }
        .emerald-label { color: #065f46; }

        .value-label { color: #71717a; font-size: 9pt; margin-bottom: 2px; }
        .value { font-size: 11pt; font-weight: bold; color: #27272a; }
        .value-large { font-size: 22pt; font-weight: bold; color: #064e3b; margin-top: 5px; }

        .grid-table {
            width: 100%;
            border-spacing: 20px 0;
            margin-bottom: 40px;
        }
        .grid-col {
            width: 50%;
            vertical-align: top;
        }

        /* Tablas */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 40px;
        }
        table.data-table th {
            background-color: #f8fafc;
            color: #64748b;
            font-size: 8pt;
            text-transform: uppercase;
            padding: 10px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        table.data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9pt;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .subtotal-row { background-color: #fafafa; }
        .subtotal-label { 
            font-size: 8pt; 
            font-weight: bold; 
            color: #71717a; 
            text-transform: uppercase;
        }
        .subtotal-value { font-size: 14pt; font-weight: bold; color: #18181b; }

        .description-box { margin-bottom: 30px; }
        .description-text { font-size: 10pt; color: #3f3f46; white-space: pre-wrap; }

        .extension-card {
            display: inline-block;
            width: 30%;
            margin-right: 2%;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #e4e4e7;
            border-radius: 5px;
        }
        .extension-name { font-size: 8pt; font-weight: bold; }
        .extension-price { font-size: 10pt; font-weight: bold; color: #059669; }
        .extension-type { font-size: 7pt; color: #a1a1aa; text-transform: uppercase; }

        .keep-together {
            page-break-inside: avoid;
        }

        .footer-total {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 3px solid #18181b;
            text-align: right;
        }
        .total-label { font-size: 10pt; font-weight: bold; color: #71717a; text-transform: uppercase; }
        .total-value { font-size: 30pt; font-weight: bold; color: #18181b; margin-top: 5px; }
        .balance-label { font-size: 12pt; font-weight: bold; }
        .positive { color: #059669; }
        .negative { color: #dc2626; }

        .page-footer {
            margin-top: 50px;
            text-align: center;
            font-size: 7pt;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <!-- Logo en negro -->
        @if($logoBase64)
        <div style="float: left; width: 150px; margin-bottom: 20px;">
            <img src="{{ $logoBase64 }}" style="width: 100%; height: auto;">
        </div>
        @endif
        <div class="status-badge">{{ $mantenimiento->estado }}</div>
        <div class="clear"></div>
        <h1 class="header-title">{{ $mantenimiento->aplicacion }}</h1>
        <div class="header-subtitle">
            Informe de Mantenimiento &bull; 
            @if($periodo['month'] === 'all')
                Año {{ $periodo['year'] }}
            @else
                {{ \Carbon\Carbon::create()->month((int)$periodo['month'])->locale('es')->monthName }} {{ $periodo['year'] }}
            @endif
        </div>
    </div>

    <table class="grid-table">
        <tr>
            <td class="grid-col">
                <div class="card bg-zinc">
                    <div class="label">Información General</div>
                    <div>
                        <div class="value-label">Cliente</div>
                        <div class="value">{{ $mantenimiento->cliente->name }}</div>
                    </div>
                    <table style="width: 100%; margin-top: 15px;">
                        <tr>
                            <td>
                                <div class="value-label">Periodicidad</div>
                                <div class="value" style="font-size: 10pt; text-transform: capitalize;">{{ $mantenimiento->tipo_pago }}</div>
                            </td>
                            <td>
                                <div class="value-label">Importe Cuota</div>
                                <div class="value" style="font-size: 10pt;">{{ number_format($mantenimiento->importe, 2, ',', '.') }}€</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="grid-col">
                <div class="card bg-emerald">
                    <div class="label emerald-label">Resumen del Periodo</div>
                    <div class="value-label" style="color: #065f46;">Ingresos Totales</div>
                    <div class="value-large" style="font-weight: bold !important;">{{ number_format($stats['ingreso'], 2, ',', '.') }}€</div>
                    
                    <div style="margin-top: 15px; border-top: 1px solid #d1fae5; padding-top: 10px;">
                        <table style="width: 100%;">
                            <tr>
                                <td class="value-label" style="color: #065f46;">Tiempo Invertido</td>
                                <td class="value text-right" style="color: #064e3b; font-weight: bold !important;">
                                    @php
                                        $h = floor($stats['minutos'] / 60);
                                        $m = $stats['minutos'] % 60;
                                        echo $h . 'h ' . ($m > 0 ? $m . 'min' : '');
                                    @endphp
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @if($mantenimiento->descripcion)
    <div class="description-box">
        <div class="label">Descripción / Notas</div>
        <div class="description-text">{{ $mantenimiento->descripcion }}</div>
    </div>
    @endif

    @if($mantenimiento->servicios && $mantenimiento->servicios->count() > 0)
    <div style="margin-bottom: 30px;" class="keep-together">
        <div class="label">Servicios Realizados (Periodo)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 55%">Concepto</th>
                    <th style="width: 15%">Fecha</th>
                    <th style="width: 15%" class="text-center">Tiempo</th>
                    <th style="width: 15%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mantenimiento->servicios as $servicio)
                <tr>
                    <td>
                        <div class="font-bold">{{ $servicio->descripcion }}</div>
                    </td>
                    <td style="color: #52525b;">{{ $servicio->fecha->format('d/m/Y') }}</td>
                    <td class="text-center">
                        @php
                            $h = floor($servicio->duracion_minutos / 60);
                            $m = $servicio->duracion_minutos % 60;
                            $timeStr = $m > 0 ? "{$h}h {$m}min" : "{$h}h";
                        @endphp
                        {{ $timeStr }}
                    </td>
                    <td class="text-right font-bold">
                        @php
                            $precio = $servicio->precio_hora !== null ? (float)$servicio->precio_hora : (float)$precioHoraFallback;
                            $coste = ($servicio->duracion_minutos / 60) * $precio;
                        @endphp
                        {{ number_format($coste, 2, ',', '.') }}€
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="subtotal-row">
                    <td colspan="3" class="text-right subtotal-label">Subtotal Servicios</td>
                    <td class="text-right subtotal-value" style="font-weight: bold !important;">{{ number_format($stats['coste_servicios'], 2, ',', '.') }}€</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    <div>
        <div class="label">Desglose de Costes (Periodo)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 70%">Concepto de Gasto</th>
                    <th style="width: 30%" class="text-right">Importe</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Servicios (Tiempo dedicado)</td>
                    <td class="text-right font-bold">{{ number_format($stats['coste_servicios'], 2, ',', '.') }}€</td>
                </tr>
                <tr>
                    <td>Extensiones y Licencias asignadas</td>
                    <td class="text-right font-bold">{{ number_format($stats['coste_extensiones'], 2, ',', '.') }}€</td>
                </tr>
                <tr>
                    <td>Infraestructura (Hosting, Software, etc.)</td>
                    <td class="text-right font-bold">{{ number_format($stats['coste_software'], 2, ',', '.') }}€</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="subtotal-row">
                    <td class="text-right subtotal-label">Gasto Total del Periodo</td>
                    <td class="text-right subtotal-value" style="font-weight: bold !important;">
                        @php
                            $gastoTotal = $stats['coste_servicios'] + $stats['coste_extensiones'] + $stats['coste_software'];
                        @endphp
                        {{ number_format($gastoTotal, 2, ',', '.') }}€
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($mantenimiento->extensiones->count() > 0)
    <div style="margin-top: 30px;" class="keep-together">
        <div class="label" style="margin-bottom: 25px;">Extensiones Activas</div>
        <div style="margin-top: 10px;">
            @foreach($mantenimiento->extensiones as $extension)
            <div class="extension-card">
                <div class="extension-name">{{ $extension->nombre }}</div>
                <div class="extension-price">
                    {{ number_format($extension->pivot->precio_aplicado ?? $extension->precio, 2, ',', '.') }}€
                </div>
                <div class="extension-type">{{ $extension->tipo_licencia }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="footer-total keep-together">
        <div class="total-label">Rentabilidad (Balance Final)</div>
        <div class="total-value {{ $stats['balance'] >= 0 ? 'positive' : 'negative' }}">
            {{ number_format($stats['balance'], 2, ',', '.') }}€
        </div>
    </div>

    <div class="page-footer">
    </div>
</body>
</html>
