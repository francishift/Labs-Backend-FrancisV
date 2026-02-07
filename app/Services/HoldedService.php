<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Presupuesto;
use App\Models\Client;
use App\Models\Factura;

class HoldedService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.holded.com/api/';

    public function __construct()
    {
        $this->apiKey = config('services.holded.key') ?? '';
    }

    /**
     * Get documents by type (estimate, invoice, etc.)
     *
     * @param string $type
     * @param array $params
     * @return array
     */
    public function getDocuments(string $type, array $params = []): array
    {
        if (empty($this->apiKey)) {
            Log::error('Holded API Key is missing.');
            return [
                'success' => false,
                'data' => [],
                'error' => 'API Key de Holded no configurada.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "invoicing/v1/documents/{$type}", $params);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => is_array($data) ? $data : [],
                    'error' => null,
                ];
            }

            $errorMessage = "Error de API Holded: " . $response->status() . " " . $response->body();
            Log::error($errorMessage);
            
            return [
                'success' => false,
                'data' => [],
                'error' => 'Error al conectar con Holded (Status ' . $response->status() . ').',
            ];
        } catch (\Exception $e) {
            Log::error("Holded API exception: " . $e->getMessage());
            return [
                'success' => false,
                'data' => [],
                'error' => 'Excepción al conectar con la API de Holded.',
            ];
        }
    }

    public function syncDocuments(string $type, array $params = []): array
    {
        $result = $this->getDocuments($type, $params);

        if ($result['success']) {
            foreach ($result['data'] as $doc) {
                $data = [
                    'contact_id' => $doc['contactId'] ?? null,
                    'contact_name' => $doc['contactName'] ?? null,
                    'contact' => $doc['contact'] ?? null,
                    'date' => $doc['date'] ?? null,
                    'total' => $doc['total'] ?? 0,
                    'status' => $doc['status'] ?? 0,
                    'raw_data' => $doc,
                ];

                if ($type === 'estimate') {
                    Presupuesto::updateOrCreate(
                        ['holded_id' => $doc['id']],
                        $data
                    );
                } elseif ($type === 'invoice') {
                    Factura::updateOrCreate(
                        ['holded_id' => $doc['id']],
                        $data
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Get document PDF binary
     *
     * @param string $type
     * @param string $id
     * @return string|null
     */
    public function getDocumentPdf(string $type, string $id): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('Holded API Key is missing.');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "invoicing/v1/documents/{$type}/{$id}/pdf");

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            }

            return null;
            return null;
        } catch (\Exception $e) {
            Log::error("Holded API PDF exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all contacts from Holded
     */
    public function getContacts(): array
    {
        if (empty($this->apiKey)) {
            Log::error('Holded API Key is missing.');
            return [
                'success' => false,
                'data' => [],
                'error' => 'API Key de Holded no configurada.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . "invoicing/v1/contacts");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json() ?? [],
                    'error' => null,
                ];
            }

            Log::error("Holded API Contacts error: " . $response->status() . " " . $response->body());
            return [
                'success' => false,
                'data' => [],
                'error' => 'Error al obtener contactos de Holded.',
            ];
        } catch (\Exception $e) {
            Log::error("Holded API Contacts exception: " . $e->getMessage());
            return [
                'success' => false,
                'data' => [],
                'error' => 'Excepción al obtener contactos de Holded.',
            ];
        }
    }

    /**
     * Sync Holded contacts with local clients
     */
    public function syncContacts(): array
    {
        $result = $this->getContacts();

        if ($result['success']) {
            $processedHoldedIds = [];

            foreach ($result['data'] as $contactData) {
                $type = $contactData['type'] ?? '';
                
                if ($type === 'client') {
                    $client = $this->updateOrCreateClientFromHolded($contactData);
                    if ($client && $client->contact) {
                        $processedHoldedIds[] = $client->contact;
                    }
                }
            }

            $this->cleanupNonClients($processedHoldedIds);
        }

        return $result;
    }

    /**
     * Internal method to process a single contact from Holded
     */
    private function updateOrCreateClientFromHolded(array $contact): ?Client
    {
        $cifNif = $contact['code'] ?? null;
        $holdedId = $contact['id'] ?? null;

        if (!$cifNif && !$holdedId) {
            return null;
        }

        // Try to find by contact ID first
        $client = $holdedId ? Client::where('contact', $holdedId)->first() : null;

        // If not found, try to find by CIF/NIF
        if (!$client && $cifNif) {
            $client = Client::where('cif_nif', $cifNif)->first();
        }

        $data = [
            'name' => $contact['name'] ?? null,
            'email' => $contact['email'] ?? null,
            'phone' => $contact['phone'] ?? null,
            'mobile' => $contact['mobile'] ?? null,
            'address' => $contact['billAddress']['address'] ?? null,
            'city' => $contact['billAddress']['city'] ?? null,
            'zip_code' => $contact['billAddress']['postalCode'] ?? null,
            'province' => $contact['billAddress']['province'] ?? null,
            'country' => $contact['billAddress']['country'] ?? null,
            'contact' => $holdedId,
        ];

        // Remove null values to avoid overwriting with null
        $data = array_filter($data, fn($v) => !is_null($v));

        if ($client) {
            $client->update($data);
        } else {
            $data['cif_nif'] = $cifNif;
            $client = Client::create($data);
        }

        return $client;
    }

    /**
     * Cleanup clients that are no longer "client" type in Holded
     */
    private function cleanupNonClients(array $validHoldedIds): void
    {
        // Find clients that have a Holded ID (contact field) but are NOT in the valid list
        $clientsToCheck = Client::whereNotNull('contact')
            ->whereNotIn('contact', $validHoldedIds)
            ->get();

        foreach ($clientsToCheck as $client) {
            // Check if they have associated records
            $hasRelations = $client->proyectos()->exists() || $client->mantenimientos()->exists();

            if ($hasRelations) {
                // Safety: Just unlink from Holded but keep the record
                $client->update(['contact' => null]);
                Log::info("Client '{$client->name}' (ID: {$client->id}) is no longer a 'client' in Holded but has local data. Unlinked.");
            } else {
                // Safe to delete
                $client->delete();
                Log::info("Client '{$client->name}' (ID: {$client->id}) deleted as it is no longer a 'client' in Holded and has no local data.");
            }
        }
    }
}
