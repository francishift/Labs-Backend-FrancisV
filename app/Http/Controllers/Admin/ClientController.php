<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Imports\ClientImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class ClientController extends Controller
{
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
            'excel_created_at' => 'nullable|date',
        ]);

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
            'excel_created_at' => 'nullable|date',
        ]);

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
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        Excel::import(new ClientImport, $request->file('file'));

        return back()
            ->with('success', 'Importación finalizada. Los datos existentes han sido actualizados y los nuevos añadidos.');
    }

    public function show(Client $client)
    {
        $client->load([
            'proyectos:id,proyecto,presupuesto,estado,fecha_inicio,fecha_fin,client_id,updated_at',
            'proyectos.extensiones:id,nombre',
            'mantenimientos:id,aplicacion,importe,estado,fecha_inicio,tipo_pago,client_id',
            'mantenimientos.extensiones:id,nombre'
        ]);

        // Pagination between clients
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
            'pagination' => $paginationData
        ]);
    }
}
