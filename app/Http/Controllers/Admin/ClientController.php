<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Imports\ClientImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use App\Models\Presupuesto;
use App\Models\Factura;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Services\DocumentPdfService;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientController extends Controller
{
    private DocumentPdfService $pdfService;

    public function __construct(DocumentPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function index(Request $request)
    {
        $clients = Client::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('cif_nif', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(StoreClientRequest $request)
    {
        $data = $request->validated();

        if (!empty($data['secondary_contacts'])) {
            $data['secondary_contacts'] = array_map('trim', explode(',', $data['secondary_contacts']));
        } else {
            $data['secondary_contacts'] = null;
        }

        Client::create($data);

        return back()->with('success', 'Cliente creado correctamente.');
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $data = $request->validated();

        if (!empty($data['secondary_contacts'])) {
            $data['secondary_contacts'] = array_filter(array_map('trim', explode(',', $data['secondary_contacts'])));
        } else {
            $data['secondary_contacts'] = null;
        }

        $client->update($data);

        return back()->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return back()->with('success', 'Cliente eliminado correctamente.');
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

        return back()->with('success', 'Importación finalizada. Los datos existentes han sido actualizados y los nuevos añadidos.');
    }

    public function show(Client $client)
    {
        $client->load([
            'proyectos:id,proyecto,presupuesto,estado,fecha_inicio,fecha_fin,client_id,presupuesto_id,updated_at',
            'proyectos.extensiones:id,nombre,precio,tipo_licencia',
            'mantenimientos:id,aplicacion,importe,estado,fecha_inicio,tipo_pago,client_id',
            'mantenimientos.extensiones:id,nombre,precio,tipo_licencia'
        ]);

        $presupuestos = Presupuesto::where('client_id', $client->id)
            ->orderBy('date', 'desc')
            ->get();

        $facturas = Factura::where('client_id', $client->id)
            ->orderBy('date', 'desc')
            ->get();

        // Paginación entre clientes
        $allClientIds = Client::query()
            ->orderBy('name')
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($client->id, $allClientIds);
        $currentPage = $currentIndex !== false ? $currentIndex + 1 : 1;

        $paginator = new LengthAwarePaginator(
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
        $pdf = $this->pdfService->generateClientPdf($client);
        
        if ($request->has('download')) {
            return $pdf->download("Cliente-{$client->name}.pdf");
        }

        return $pdf->stream("Cliente-{$client->name}.pdf");
    }

    public function getPresupuestos(Client $client)
    {
        try {
            $presupuestos = Presupuesto::with('cliente:id,name')->where('client_id', $client->id)
                ->orderBy('date', 'desc')
                ->get(['id', 'client_id', 'total', 'date', 'number'])
                ->map(function ($p) {
                    $docName = $p->number ?? 'Propuesta N/A';
                    $clientName = $p->cliente ? $p->cliente->name : 'Sin Cliente';
                    $timestamp = is_numeric($p->date) ? $p->date : strtotime($p->date);
                    return [
                        'id' => $p->id,
                        'name' => "{$docName} - {$clientName} - " . date('d/m/Y', $timestamp) . ' (' . number_format($p->total, 2) . '€)',
                    ];
                });

            return response()->json($presupuestos);
        } catch (\Exception $e) {
            Log::error('Error fetching budgets for client ' . $client->id . ': ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    public function getFacturas(Client $client)
    {
        try {
            $facturas = Factura::where('client_id', $client->id)
                ->orderBy('date', 'desc')
                ->get(['id', 'number', 'total', 'date', 'proyecto_id'])
                ->map(function ($f) {
                    return [
                        'id' => $f->id,
                        'name' => $f->number . ' - ' . date('d/m/Y', $f->date) . ' (' . number_format($f->total, 2) . '€)',
                        'proyecto_id' => $f->proyecto_id,
                    ];
                });

            return response()->json($facturas);
        } catch (\Exception $e) {
            Log::error('Error fetching invoices for client ' . $client->id . ': ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
}
