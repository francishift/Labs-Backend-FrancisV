<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Informe de Proyecto: {{ $proyecto->proyecto }}</title>
    <style>
        /* Tipografía estándar para PDF */
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #18181b;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #e4e4e7;
            padding-bottom: 20px;
            margin-bottom: 40px;
        }
        .header-title {
            font-size: 24pt;
            font-weight: bold;
            margin: 0;
            color: #18181b;
        }
        .header-subtitle {
            color: #71717a;
            font-size: 10pt;
            margin-top: 5px;
        }
        .status-badge {
            float: right;
            background-color: #059669;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 10pt;
        }
        .clear { clear: both; }

        /* Asegurar consistencia en números */
        .value, .value-large, .subtotal-value, .total-value, .extension-price {
            font-family: 'Helvetica', sans-serif !important;
            font-weight: bold;
        }

        .label {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #a1a1aa;
            margin-bottom: 12px;
            line-height: 1.2;
            display: block;
        }
        .emerald-label { color: #059669; opacity: 0.7; }

        .card {
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #f4f4f5;
            margin-bottom: 20px;
        }
        .bg-zinc { background-color: #fafafa; border-color: #f4f4f5; }
        .bg-emerald { background-color: #ecfdf5; border-color: #d1fae5; }

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
        .total-value { font-size: 36pt; font-weight: bold; color: #18181b; margin-top: 5px; }

        .overhead-box {
            background-color: #fafafa;
            border-left: 4px solid #18181b;
            padding: 15px;
            margin-bottom: 30px;
        }

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
        <div class="status-badge">{{ $proyecto->estado }}</div>
        <div class="clear"></div>
        <h1 class="header-title">{{ $proyecto->proyecto }}</h1>
        <div class="header-subtitle">Informe de Proyecto &bull; {{ now()->format('d/m/Y') }}</div>
    </div>

    <table class="grid-table">
        <tr>
            <td class="grid-col">
                <div class="card bg-zinc">
                    <div class="label">Información General</div>
                    <div>
                        <div class="value-label">Cliente</div>
                        <div class="value">{{ $proyecto->client->name }}</div>
                    </div>
                    <table style="width: 100%; margin-top: 15px;">
                        <tr>
                            <td>
                                <div class="value-label">Fecha Inicio</div>
                                <div class="value" style="font-size: 10pt;">{{ $proyecto->fecha_inicio->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <div class="value-label">Fecha Fin</div>
                                <div class="value" style="font-size: 10pt;">{{ $proyecto->fecha_fin ? $proyecto->fecha_fin->format('d/m/Y') : 'Pendiente' }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="grid-col">
                <div class="card bg-emerald">
                    <div class="label emerald-label">Resumen Financiero</div>
                    <div class="value-label" style="color: #065f46;">Presupuesto del Proyecto</div>
                    <div class="value-large" style="font-weight: bold !important;">{{ number_format($proyecto->presupuesto, 2, ',', '.') }}€</div>
                    
                    <div style="margin-top: 15px; border-top: 1px solid #d1fae5; padding-top: 10px;">
                        <table style="width: 100%;">
                            <tr>
                                <td class="value-label" style="color: #065f46;">Coste Acumulado</td>
                                <td class="value text-right" style="color: #064e3b; font-weight: bold !important;">{{ number_format($grandTotal, 2, ',', '.') }}€</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @if($proyecto->descripcion)
    <div class="description-box">
        <div class="label">Descripción / Notas</div>
        <div class="description-text">{{ $proyecto->descripcion }}</div>
    </div>
    @endif

    <div>
        <div class="label">Servicios Realizados</div>
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
                @foreach($proyecto->servicios as $servicio)
                <tr>
                    <td>
                        <div class="font-bold">{{ $servicio->servicio }}</div>
                        @if($servicio->descripcion)
                        <div style="font-size: 8pt; color: #71717a; margin-top: 3px;">{{ Str::limit($servicio->descripcion, 100) }}</div>
                        @endif
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
                            $precioHora = $servicio->precio_hora ?? 0;
                            $coste = ($servicio->duracion_minutos / 60) * $precioHora + ($servicio->precio ?? 0);
                        @endphp
                        {{ number_format($coste, 2, ',', '.') }}€
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="subtotal-row">
                    <td colspan="3" class="text-right subtotal-label">Subtotal Servicios</td>
                    <td class="text-right subtotal-value" style="font-weight: bold !important;">{{ number_format($servicesTotal, 2, ',', '.') }}€</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($proyecto->extensiones->count() > 0)
    <div style="margin-top: 30px;" class="keep-together">
        <div class="label" style="margin-bottom: 25px;">Extensiones y Licencias</div>
        <div style="margin-top: 10px;">
            @foreach($proyecto->extensiones as $extension)
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

    <div class="overhead-box" style="margin-top: 30px;">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="font-bold">Mantenimiento de Software y Hosting</div>
                    <div style="font-size: 8pt; color: #71717a;">Prorrateo de gastos globales aplicados</div>
                </td>
                <td class="text-right font-bold" style="font-size: 12pt;">
                    {{ number_format($costeSoftware, 2, ',', '.') }}€
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-total">
        <div class="total-label">Coste Total del Proyecto</div>
        <div class="total-value">{{ number_format($grandTotal, 2, ',', '.') }}€</div>
    </div>

    <div class="page-footer">
    </div>
</body>
</html>
