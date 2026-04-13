<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Resumen de Cliente: {{ $client->name }}</title>
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

        /* Tipografía estándar para PDF */
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Lexend', sans-serif;
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
        .clear { clear: both; }

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
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #f4f4f5;
            margin-bottom: 20px;
        }
        .bg-zinc { background-color: #fafafa; border-color: #f4f4f5; }
        .bg-emerald { background-color: #ecfdf5; border-color: #d1fae5; }

        .value-label { color: #71717a; font-size: 9pt; margin-bottom: 2px; }
        .value { font-size: 11pt; font-weight: bold; color: #27272a; }
        .value-large { font-size: 18pt; font-weight: bold; color: #064e3b; margin-top: 5px; }

        .grid-table {
            width: 100%;
            border-spacing: 20px 0;
            margin-bottom: 20px;
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
            margin-bottom: 30px;
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
        .font-bold { font-weight: bold; }
        
        .keep-together {
            page-break-inside: avoid;
        }

        .extension-badge {
            display: inline-block;
            background-color: #f4f4f5;
            color: #3f3f46;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8pt;
            margin-right: 5px;
            margin-bottom: 5px;
            border: 1px solid #e4e4e7;
        }
        .extension-source {
            font-size: 7pt;
            color: #71717a;
            display: block;
        }

        .status-badge {
            font-size: 7pt;
            text-transform: uppercase;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .status-indigo { background-color: #e0e7ff; color: #4338ca; }
        .status-emerald { background-color: #d1fae5; color: #065f46; }
        .status-amber { background-color: #fef3c7; color: #92400e; }
        .status-zinc { background-color: #f4f4f5; color: #3f3f46; }

    </style>
</head>
<body>
    <div class="header">
        @if($logoBase64)
        <div style="float: left; width: 150px; margin-bottom: 20px;">
            <img src="{{ $logoBase64 }}" style="width: 100%; height: auto;">
        </div>
        @endif
        <div class="clear"></div>
        <h1 class="header-title">{{ $client->name }}</h1>
        <div class="header-subtitle">Resumen de Cuenta de Cliente &bull; {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table class="grid-table">
        <tr>
            <td class="grid-col">
                <div class="card bg-zinc">
                    <div class="label">Información de Contacto</div>
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 50%;">
                                <div class="value-label">CIF/NIF</div>
                                <div class="value">{{ $client->cif_nif ?: 'S/N' }}</div>
                            </td>
                            <td>
                                <div class="value-label">Email</div>
                                <div class="value" style="font-size: 10pt;">{{ $client->email ?: 'S/N' }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-top: 15px;">
                                <div class="value-label">Teléfono/Móvil</div>
                                <div class="value">{{ implode(' / ', array_filter([$client->phone, $client->mobile])) ?: 'S/N' }}</div>
                            </td>
                            <td style="padding-top: 15px;">
                                <div class="value-label">Ubicación</div>
                                <div class="value">{{ implode(', ', array_filter([$client->city, $client->province])) ?: 'Desconocida' }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="grid-col">
                <div class="card bg-emerald">
                    <div class="label emerald-label">Resumen Financiero Activo</div>
                    <table style="width: 100%;">
                        <tr>
                            <td>
                                <div class="value-label" style="color: #065f46;">Presupuesto Proyectos Proceso</div>
                                <div class="value-large">{{ number_format($client->active_projects_budget, 2, ',', '.') }}€</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-top: 10px; border-top: 1px solid #d1fae5;">
                                <div class="value-label" style="color: #065f46;">Ingreso Mensual Mantenimiento</div>
                                <div class="value-large" style="font-size: 16pt;">{{ number_format($client->monthly_maintenance_income, 2, ',', '.') }}€</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="keep-together">
        <div class="label">Proyectos en Curso</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50%">Proyecto</th>
                    <th style="width: 20%">Inicio</th>
                    <th style="width: 30%" class="text-right">Presupuesto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($client->proyectos->where('estado', 'En proceso') as $proyecto)
                <tr>
                    <td class="font-bold">{{ $proyecto->proyecto }}</td>
                    <td>{{ $proyecto->fecha_inicio ? $proyecto->fecha_inicio->format('d/m/Y') : '-' }}</td>
                    <td class="text-right font-bold">{{ number_format($proyecto->presupuesto, 2, ',', '.') }}€</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center; color: #71717a; font-style: italic;">No hay proyectos activos.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="keep-together">
        <div class="label">Mantenimientos Activos</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50%">Aplicación</th>
                    <th style="width: 20%">Pago</th>
                    <th style="width: 30%" class="text-right">Importe</th>
                </tr>
            </thead>
            <tbody>
                @forelse($client->mantenimientos->where('estado', 'en curso') as $mante)
                <tr>
                    <td class="font-bold">{{ $mante->aplicacion }}</td>
                    <td style="text-transform: uppercase; font-size: 8pt;">{{ $mante->tipo_pago }}</td>
                    <td class="text-right font-bold">{{ number_format($mante->importe, 2, ',', '.') }}€</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center; color: #71717a; font-style: italic;">No hay mantenimientos activos.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(is_array($presupuestos) ? count($presupuestos) > 0 : ($presupuestos && $presupuestos->count() > 0))
    <div class="keep-together">
        <div class="label">Presupuestos Recientes</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%">Número</th>
                    <th style="width: 20%">Fecha</th>
                    <th style="width: 30%">Estado</th>
                    <th style="width: 30%" class="text-right">Total (IVA inc)</th>
                </tr>
            </thead>
            <tbody>
                @foreach(is_array($presupuestos) ? array_slice($presupuestos, 0, 10) : $presupuestos->take(10) as $presu)
                <tr>
                    <td class="font-bold">{{ $presu->raw_data['docNumber'] ?? '-' }}</td>
                    <td>{{ date('d/m/Y', $presu->date) }}</td>
                    <td>
                        @php
                            $statusLabel = ['Pendiente', 'Aceptado', 'Rechazado'][$presu->status] ?? 'Desconocido';
                            $statusClass = ['status-amber', 'status-emerald', 'status-zinc'][$presu->status] ?? 'status-zinc';
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="text-right font-bold">{{ number_format($presu->total, 2, ',', '.') }}€</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @php
        $uniqueExtensions = [];
        foreach($client->proyectos as $p) {
            foreach($p->extensiones as $ext) {
                if (!isset($uniqueExtensions[$ext->id])) {
                    $uniqueExtensions[$ext->id] = (object)['nombre' => $ext->nombre, 'source' => 'Proyecto: ' . $p->proyecto];
                }
            }
        }
        foreach($client->mantenimientos as $m) {
            foreach($m->extensiones as $ext) {
                if (!isset($uniqueExtensions[$ext->id])) {
                    $uniqueExtensions[$ext->id] = (object)['nombre' => $ext->nombre, 'source' => 'Mantenimiento: ' . $m->aplicacion];
                }
            }
        }
    @endphp

    @if(count($uniqueExtensions) > 0)
    <div class="keep-together" style="margin-top: 20px;">
        <div class="label">Extensiones y Herramientas</div>
        <div style="margin-top: 10px;">
            @foreach($uniqueExtensions as $ext)
            <div class="extension-badge">
                <span class="font-bold">{{ $ext->nombre }}</span>
                <span class="extension-source">{{ $ext->source }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($client->proyectos->where('estado', 'Finalizado')->count() > 0)
    <div class="keep-together" style="margin-top: 30px;">
        <div class="label">Histórico de Proyectos Finalizados</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 70%">Proyecto</th>
                    <th style="width: 30%" class="text-right">Presupuesto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($client->proyectos->where('estado', 'Finalizado') as $proyecto)
                <tr style="opacity: 0.7;">
                    <td>{{ $proyecto->proyecto }}</td>
                    <td class="text-right">{{ number_format($proyecto->presupuesto, 2, ',', '.') }}€</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</body>
</html>
