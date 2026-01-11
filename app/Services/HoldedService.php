<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

            Log::error("Holded API PDF error: " . $response->status() . " " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Holded API PDF exception: " . $e->getMessage());
            return null;
        }
    }
}
