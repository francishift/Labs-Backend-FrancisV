<?php

namespace App\Services;

use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Google\Cloud\DocumentAI\V1\RawDocument;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Log;

class DocumentAiService
{
    protected string $processorId;
    protected string $location;
    protected string $projectId;

    public function __construct()
    {
        $this->processorId = env('GOOGLE_DOCUMENT_AI_PROCESSOR_ID');
        $this->location = 'eu'; // We fixed it to EU
        $this->projectId = 'labs-francis'; // From the JSON
    }

    /**
     * Extract data from a PDF file using Google Document AI.
     */
    public function extractInvoiceData(string $fileBinary): array
    {
        try {
            $credentialsPath = base_path(env('GOOGLE_APPLICATION_CREDENTIALS'));
            $credentialsArray = json_decode(file_get_contents($credentialsPath), true);
            
            // ServiceAccountCredentials expects an array as the second argument
            $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
            $credentials = new ServiceAccountCredentials($scopes, $credentialsArray);

            $client = new DocumentProcessorServiceClient([
                'credentials' => $credentials,
                'apiEndpoint' => 'eu-documentai.googleapis.com:443'
            ]);

            $name = $client->processorName($this->projectId, $this->location, $this->processorId);

            $rawDocument = new RawDocument();
            $rawDocument->setContent($fileBinary);
            $rawDocument->setMimeType('application/pdf');

            $request = new ProcessRequest();
            $request->setName($name);
            $request->setRawDocument($rawDocument);

            $response = $client->processDocument($request);

            $document = $response->getDocument();
            $entities = $document->getEntities();

            $data = [
                'total_amount' => 0,
                'net_amount' => 0,
                'tax_amount' => 0,
                'currency' => 'EUR',
                'invoice_date' => null,
                'supplier_name' => 'Proveedor desconocido',
                'invoice_id' => null,
                'raw' => [],
            ];

            foreach ($entities as $entity) {
                $type = $entity->getType();
                $value = $entity->getMentionText();
                
                $data['raw'][$type] = $value;

                switch ($type) {
                    case 'total_amount':
                        $data['total_amount'] = $this->parseAmount($value);
                        // Extract currency if normalized
                        if ($entity->getNormalizedValue() && $entity->getNormalizedValue()->getMoneyValue()) {
                             $data['currency'] = $entity->getNormalizedValue()->getMoneyValue()->getCurrencyCode() ?: $data['currency'];
                        }
                        break;
                    case 'net_amount':
                        $baseAmount = $this->parseAmount($value);
                        if ($baseAmount > 0) $data['net_amount'] = $baseAmount;
                        break;
                    case 'total_tax_amount':
                    case 'tax_amount':
                        $tax = $this->parseAmount($value);
                        if ($tax > 0) $data['tax_amount'] += $tax; // Aggregate if multiple
                        break;
                    case 'currency':
                        $data['currency'] = $value;
                        break;
                    case 'invoice_date':
                        $data['invoice_date'] = $this->parseDate($entity->getNormalizedValue() ? $entity->getNormalizedValue()->getText() : $value);
                        break;
                    case 'supplier_name':
                        $data['supplier_name'] = $value;
                        break;
                    case 'invoice_id':
                        $data['invoice_id'] = $value;
                        break;
                }

                // Sub-entities check (for complex tax structures)
                foreach ($entity->getProperties() as $prop) {
                    if ($prop->getType() === 'tax_amount') {
                        $tax = $this->parseAmount($prop->getMentionText());
                        if ($tax > 0) $data['tax_amount'] += $tax;
                    }
                }
            }

            // --- Supplier Validation Heuristic ---
            // If the AI confuses the receiver (you) with the supplier, we fix it.
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
                Log::warning("Document AI confused receiver with supplier: " . $data['supplier_name']);
                
                // If we also detected a receiver_name, maybe that's the real supplier?
                if (isset($data['raw']['receiver_name'])) {
                    $receiver = mb_strtoupper($data['raw']['receiver_name']);
                    $receiverIsMe = false;
                    foreach ($myNames as $name) {
                        if (str_contains($receiver, $name)) {
                            $receiverIsMe = true;
                            break;
                        }
                    }
                    
                    if (!$receiverIsMe) {
                        $data['supplier_name'] = $data['raw']['receiver_name'];
                    } else {
                        $data['supplier_name'] = 'Revisión manual necesaria';
                    }
                } else {
                    $data['supplier_name'] = 'Revisión manual necesaria';
                }
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Document AI Extraction Failed: ' . $e->getMessage());
            return [];
        }
    }
    private function parseAmount($value): float
    {
        if (empty($value)) return 0;
        
        // Remove currency symbols and spaces
        $clean = preg_replace('/[^\d,.]/', '', $value);
        
        // Handle European format (1.234,56)
        if (preg_match('/^\d+(\.\d{3})*(,\d+)?$/', $clean)) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }
        // Handle US format (1,234.56)
        elseif (preg_match('/^\d+(,\d{3})*(\.\d+)?$/', $clean)) {
            $clean = str_replace(',', '', $clean);
        }

        return (float) $clean;
    }

    private function parseDate(string $value): ?string
    {
        try {
            // Document AI usually returns YYYY-MM-DD in normalizedValue
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value;
            }
            $timestamp = strtotime($value);
            return $timestamp ? date('Y-m-d', $timestamp) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
