<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Imports\ClientImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use App\Services\HoldedService;
use App\Models\Presupuesto;

class ClientController extends Controller
{
    protected HoldedService $holdedService;

    public function __construct(HoldedService $holdedService)
    {
        $this->holdedService = $holdedService;
    }

    public function index(Request $request)
    {
        $clients = Client::query()
            ->select(['id', 'name', 'cif_nif', 'email', 'city'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('cif_nif', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'cif_nif' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'contact' => 'nullable|string|max:255',
            'secondary_contacts' => 'nullable|string',
            'excel_created_at' => 'nullable|date',
        ]);

        if (!empty($data['secondary_contacts'])) {
            $data['secondary_contacts'] = array_map('trim', explode(',', $data['secondary_contacts']));
        } else {
            $data['secondary_contacts'] = null;
        }

        Client::create($data);

        return back()
            ->with('success', 'Cliente creado correctamente.');
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'cif_nif' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'contact' => 'nullable|string|max:255',
            'secondary_contacts' => 'nullable|string',
            'excel_created_at' => 'nullable|date',
        ]);

        if (!empty($data['secondary_contacts'])) {
            $data['secondary_contacts'] = array_filter(array_map('trim', explode(',', $data['secondary_contacts'])));
        } else {
            $data['secondary_contacts'] = null;
        }

        $client->update($data);

        return back()
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return back()
            ->with('success', 'Cliente eliminado correctamente.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'mimes:xlsx,xls,csv',
                function ($attribute, $value, $fail) {
                    if ($value->getSize() > 10485760) {
                        $fail('El archivo no debe pesar más de 10 MB.');
                    }
                },
            ],
        ]);

        Excel::import(new ClientImport, $request->file('file'));

        return back()
            ->with('success', 'Importación finalizada. Los datos existentes han sido actualizados y los nuevos añadidos.');
    }

    public function show(Client $client)
    {
        $client->load([
            'proyectos:id,proyecto,presupuesto,estado,fecha_inicio,fecha_fin,client_id,presupuesto_id,updated_at',
            'proyectos.extensiones:id,nombre,precio,tipo_licencia',
            'mantenimientos:id,aplicacion,importe,estado,fecha_inicio,tipo_pago,client_id',
            'mantenimientos.extensiones:id,nombre,precio,tipo_licencia'
        ]);

        // Obtener presupuestos y facturas de Holded asociados a este cliente
        $contactIds = array_filter([
            $client->contact,
            ...($client->secondary_contacts ?? [])
        ]);

        $presupuestos = [];
        $facturas = [];

        if (!empty($contactIds)) {
            $presupuestos = Presupuesto::where(function($q) use ($contactIds) {
                    $q->whereIn('contact_id', $contactIds)
                      ->orWhereIn('contact', $contactIds);
                })
                ->orderBy('date', 'desc')
                ->get();

            $facturas = \App\Models\Factura::where(function($q) use ($contactIds) {
                    $q->whereIn('contact_id', $contactIds)
                      ->orWhereIn('contact', $contactIds);
                })
                ->orderBy('date', 'desc')
                ->get();
        }

        // Paginación entre clientes
        $allClientIds = Client::query()
            ->orderBy('name')
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($client->id, $allClientIds);
        $currentPage = $currentIndex !== false ? $currentIndex + 1 : 1;

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            [$client],
            count($allClientIds),
            1,
            $currentPage,
            ['path' => route('admin.clientes.index')]
        );

        $paginationData = $paginator->toArray();
        foreach ($paginationData['links'] as &$link) {
            if ($link['url']) {
                $urlParts = parse_url($link['url'], PHP_URL_QUERY);
                if ($urlParts) {
                    parse_str($urlParts, $query);
                    if (isset($query['page'])) {
                        $targetPageIndex = (int)$query['page'] - 1;
                        if (isset($allClientIds[$targetPageIndex])) {
                            $targetId = $allClientIds[$targetPageIndex];
                            $link['url'] = route('admin.clientes.show', $targetId);
                        }
                    }
                }
            }
        }

        return Inertia::render('Admin/Clients/Show', [
            'client' => $client,
            'presupuestos' => $presupuestos,
            'facturas' => $facturas,
            'pagination' => $paginationData,
            'stats' => [
                'active_projects_budget' => $client->active_projects_budget,
                'monthly_maintenance_income' => $client->monthly_maintenance_income,
            ]
        ]);
    }

    public function exportPdf(Request $request, Client $client)
    {
        $client->load([
            'proyectos' => fn($q) => $q->orderBy('fecha_inicio', 'desc'),
            'proyectos.extensiones',
            'mantenimientos' => fn($q) => $q->orderBy('fecha_inicio', 'desc'),
            'mantenimientos.extensiones'
        ]);

        // Presupuestos de Holded
        $presupuestos = [];
        if ($client->contact) {
            $presupuestos = Presupuesto::where('contact', $client->contact)
                ->orderBy('date', 'desc')
                ->get();
        }

        $logoPath = public_path('img/logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.client', compact(
            'client', 
            'presupuestos', 
            'logoBase64'
        ));
        
        if ($request->has('download')) {
            return $pdf->download("Cliente-{$client->name}.pdf");
        }

        return $pdf->stream("Cliente-{$client->name}.pdf");
    }

    public function getPresupuestos(Client $client)
    {
        if (!$client->contact && empty($client->secondary_contacts)) {
            return response()->json([]);
        }

        try {
            $contactIds = array_filter([
                $client->contact,
                ...($client->secondary_contacts ?? [])
            ]);

            // Buscar por ID de contacto en la columna 'contact_id' O en la columna 'contact' (manejo de sincronizaciones heredadas)
            $presupuestos = Presupuesto::where(function($q) use ($contactIds) {
                    $q->whereIn('contact_id', $contactIds)
                      ->orWhereIn('contact', $contactIds);
                })
                ->orderBy('date', 'desc')
                ->get(['id', 'holded_id', 'total', 'date', 'raw_data'])
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => ($p->raw_data['docNumber'] ?? $p->holded_id) . ' - ' . date('d/m/Y', $p->date) . ' (' . number_format($p->total, 2) . '€)',
                    ];
                });

            return response()->json($presupuestos);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching budgets for client ' . $client->id . ': ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    public function getFacturas(Client $client)
    {
        if (!$client->contact && empty($client->secondary_contacts)) {
            return response()->json([]);
        }

        try {
            $contactIds = array_filter([
                $client->contact,
                ...($client->secondary_contacts ?? [])
            ]);

            // Search by contact ID in 'contact_id' column OR 'contact' column (handling legacy syncs)
            $facturas = \App\Models\Factura::where(function($q) use ($contactIds) {
                    $q->whereIn('contact_id', $contactIds)
                      ->orWhereIn('contact', $contactIds);
                })
                ->orderBy('date', 'desc')
                ->get(['id', 'holded_id', 'total', 'date', 'raw_data', 'proyecto_id'])
                ->map(function ($f) {
                    return [
                        'id' => $f->id,
                        'name' => ($f->raw_data['docNumber'] ?? $f->holded_id) . ' - ' . date('d/m/Y', $f->date) . ' (' . number_format($f->total, 2) . '€)',
                        'proyecto_id' => $f->proyecto_id,
                    ];
                });

            return response()->json($facturas);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching invoices for client ' . $client->id . ': ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
}
