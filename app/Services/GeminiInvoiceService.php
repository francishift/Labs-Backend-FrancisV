<?php

namespace App\Services;

use Gemini\Client;
use Gemini\Enums\MimeType;
use Gemini\Data\Blob;
use Gemini\Data\GenerationConfig;
use Illuminate\Support\Facades\Log;

class GeminiInvoiceService
{
    protected Client $client;

    public function __construct()
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            Log::warning('GEMINI_API_KEY no está configurada.');
        }
        $this->client = \Gemini::client($apiKey);
    }

    /**
     * Extraer datos de un archivo PDF usando Google Gemini 2.5 Flash.
     */
    public function extractInvoiceData(string $fileBinary): array
    {
        try {
            $base64Pdf = base64_encode($fileBinary);

            $prompt = <<<EOT
Eres un sistema contable experto. Extrae la información solicitada de la factura PDF proporcionada.
Devuelve la información estrictamente como un objeto JSON válido que coincida exactamente con esta estructura y nada más.

{
  "total_amount": 0.0,
  "net_amount": 0.0,
  "tax_amount": 0.0,
  "currency": "EUR",
  "invoice_date": "YYYY-MM-DD",
  "supplier_name": "Nombre de la empresa o persona que emite la factura",
  "invoice_id": "Número o ID de factura",
  "receiver_name": "Nombre de la empresa o persona que recibe la factura (a quién se le factura)"
}

Reglas:
- CRÍTICO: Nunca alucines ni inventes datos. Si falta un valor, usa 0.0 para números y null para cadenas de texto.
- La factura suele estar en español. Busca "Total Factura", "Base Imponible", "Importe Neto" (net_amount), "IVA" o "Cuota" (tax_amount).
- Los montos deben ser números de punto flotante.
- `currency` debe ser el código ISO de 3 letras si se conoce, de lo contrario EUR.
- `invoice_date`: Busca SIEMPRE específicamente "Fecha factura", "Fecha de factura" o "Invoice date". Ignora otras fechas como "Fecha de vencimiento", "Periodo de facturación" o fechas de generación del documento. Formatea estrictamente como AAAA-MM-DD (ej., "10 de febrero de 2026" se convierte en "2026-02-10"). Si no se encuentra la fecha real de la factura, usa null.
- `supplier_name`: La entidad que COBRA el dinero (emisor). Busca el NOMBRE CORPORATIVO LEGAL COMPLETO (ej., "Orange Espagne, S.A.U.", no solo "Orange"). En grandes empresas, esto suele estar escrito en letra pequeña al pie o al lado de la página, generalmente junto al texto "CIF", "NIF", "S.A.", "S.L." o "Registro Mercantil". Prefiere siempre el nombre legal formal sobre el nombre comercial o logo. NO confundas al proveedor con el receptor.
- `receiver_name`: La entidad a la que se le FACTURA (receptor). Normalmente es "Francisco Valenzuela", "Labs Francis", etc., y aparece en la sección "Datos del cliente" o "Facturar a". Esta entidad es la que PAGA la factura.
EOT;

            $response = $this->client->generativeModel('gemini-2.5-flash')
                ->withGenerationConfig(new \Gemini\Data\GenerationConfig(
                    responseMimeType: \Gemini\Enums\ResponseMimeType::APPLICATION_JSON
                ))
                ->generateContent([
                $prompt,
                new Blob(
                    mimeType: MimeType::APPLICATION_PDF,
                    data: $base64Pdf
                )
            ]);

            $jsonText = $response->text();
            
            // Limpiar markdown si el modelo lo alucinó a pesar de las instrucciones
            $jsonText = str_replace(['```json', '```'], '', $jsonText);
            $jsonText = trim($jsonText);

            $extracted = json_decode($jsonText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Gemini Extraction JSON Parse Error: ' . json_last_error_msg() . ' Text: ' . $jsonText);
                return [];
            }

            // Mapa de valores por defecto basado en la estructura previa de Document AI
            $data = [
                'total_amount' => $extracted['total_amount'] ?? 0,
                'net_amount' => $extracted['net_amount'] ?? 0,
                'tax_amount' => $extracted['tax_amount'] ?? 0,
                'currency' => $extracted['currency'] ?? 'EUR',
                'invoice_date' => $extracted['invoice_date'] ?? null,
                'supplier_name' => $extracted['supplier_name'] ?? 'Proveedor desconocido',
                'invoice_id' => $extracted['invoice_id'] ?? null,
                'raw' => $extracted, // Almacenar todos los campos extraídos en bruto
            ];

            // --- Heurística de Validación de Proveedores (Igual que en el antiguo Document AI) ---
            $myNames = [
                'FRANCISCO VALENZUELA NOGALES',
                'FRANCISCO VALENZUELA',
                'LABS FRANCIS',
                'FRANCIS VALENZUELA',
                'LABS-FRANCIS'
            ];

            $detectedSupplier = mb_strtoupper($data['supplier_name']);
            $isMe = false;
            foreach ($myNames as $name) {
                if (str_contains($detectedSupplier, $name)) {
                    $isMe = true;
                    break;
                }
            }

            if ($isMe) {
                Log::warning("Gemini confused receiver with supplier: " . $data['supplier_name']);
                
                if (!empty($extracted['receiver_name'])) {
                    $receiver = mb_strtoupper($extracted['receiver_name']);
                    $receiverIsMe = false;
                    foreach ($myNames as $name) {
                        if (str_contains($receiver, $name)) {
                            $receiverIsMe = true;
                            break;
                        }
                    }
                    
                    if (!$receiverIsMe) {
                        $data['supplier_name'] = $extracted['receiver_name'];
                    } else {
                        $data['supplier_name'] = 'Revisión manual necesaria';
                    }
                } else {
                    $data['supplier_name'] = 'Revisión manual necesaria';
                }
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Gemini API Extraction Failed: ' . $e->getMessage());
            return [];
        }
    }
}
