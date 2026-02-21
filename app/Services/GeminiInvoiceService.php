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
     * Extraer datos de un archivo PDF usando Google Gemini 1.5 Flash.
     */
    public function extractInvoiceData(string $fileBinary): array
    {
        try {
            $base64Pdf = base64_encode($fileBinary);

            $prompt = <<<EOT
You are an expert accountant system. Extract the requested invoice information from the provided PDF invoice.
Return the information strictly as a valid JSON object matching exactly this structure and nothing else.

{
  "total_amount": 0.0,
  "net_amount": 0.0,
  "tax_amount": 0.0,
  "currency": "EUR",
  "invoice_date": "YYYY-MM-DD",
  "supplier_name": "Name of the company or person issuing the invoice",
  "invoice_id": "Invoice number or ID",
  "receiver_name": "Name of the company or person receiving the invoice (who it is billed to)"
}

Rules:
- CRITICAL: Never hallucinate or invent data. If a value is missing, use 0.0 for numbers and null for strings.
- The invoice might be in Spanish. Look for "Total Factura", "Base Imponible", "Importe Neto" (net_amount), "IVA" or "Cuota" (tax_amount).
- Amounts must be numeric floats.
- `currency` should be the 3-letter ISO code if known, otherwise EUR.
- `invoice_date`: ALWAYS look specifically for "Fecha factura", "Fecha de factura", or "Invoice date". Ignore other dates like "Fecha de vencimiento", "Periodo de facturación", or document generation dates. Format strictly as YYYY-MM-DD (e.g., "10 de febrero de 2026" becomes "2026-02-10"). If the true invoice date is not found, use null.
- `supplier_name`: The entity CHARGING the money. Look for the FULL LEGAL CORPORATE NAME (e.g., "Orange Espagne, S.A.U.", not just "Orange"). In large companies, this is often written in small print at the bottom or side of the page, usually next to the text "CIF", "NIF", "S.A.", "S.L.", or "Registro Mercantil". Strongly prefer the formal legal name over the commercial/logo name. Do NOT confuse the supplier with the receiver.
- `receiver_name`: The entity BEING BILLED. This is usually "Francisco Valenzuela", "Labs Francis", etc., and appears in the "Datos del cliente" or "Facturar a" section. This entity PAYS the invoice.
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
