<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Factura;
use App\Models\Client;
use App\Services\HoldedService;

class MatchHoldedContacts extends Command
{
    protected $signature = 'holded:match-contacts';
    protected $description = 'Match unlinked Holded invoices contacts to local database Clients using CIF/NIF and update the Client contact ID.';

    public function handle(HoldedService $holdedService)
    {
        $this->info('Iniciando emparejamiento de contactos de Holded...');

        // 1. Obtener todos los IDs de contacto únicos de las facturas que tenemos
        $contactIds = Factura::select('contact')->distinct()->pluck('contact')->filter()->toArray();

        // 2. Obtener la lista de todos los contactos de Holded desde la API para extraer su CIF
        $this->info('Obteniendo información de los contactos desde Holded API...');
        $holdedContacts = $holdedService->getContacts();
        $holdedContactsById = [];
        
        if ($holdedContacts['success']) {
            foreach ($holdedContacts['data'] as $c) {
                $holdedContactsById[$c['id']] = $c;
            }
        } else {
            $this->error('Error al obtener contactos de Holded. Abortando.');
            return 1;
        }

        // 3. Obtener los clientes locales por su CIF/NIF (limpio de espacios o guiones)
        $clientsByCif = Client::whereNotNull('cif_nif')->get()->keyBy(function($c) {
            return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $c->cif_nif));
        });

        $matchedCount = 0;
        $unmatchedCount = 0;

        $this->info('Comparando y actualizando clientes...');
        $bar = $this->output->createProgressBar(count($contactIds));

        foreach ($contactIds as $contactId) {
            // Verificar si el contacto ya está enlazado a un cliente (por 'contact' o 'secondary_contacts')
            $localClient = Client::where('contact', $contactId)
                        ->orWhereJsonContains('secondary_contacts', $contactId)
                        ->first();
                        
            if ($localClient) {
                $bar->advance();
                continue; // Ya está emparejado
            }
            
            // Si no está emparejado, buscamos el CIF en la data de Holded
            if (isset($holdedContactsById[$contactId])) {
                $holdedContact = $holdedContactsById[$contactId];
                $cif = $holdedContact['code'] ?? null; // 'code' suele ser el CIF en Holded
                $name = $holdedContact['name'] ?? 'Desconocido';
                
                if ($cif) {
                    $cleanCif = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $cif));
                    
                    if (isset($clientsByCif[$cleanCif])) {
                        // ¡HAY COINCIDENCIA POR CIF!
                        $matchedClient = $clientsByCif[$cleanCif];
                        
                        $this->line('');
                        $this->info("¡Match encontrado! Holded: {$name} ({$cif}) => Local: {$matchedClient->name}");
                        
                        // ACTUALIZAR el cliente local para enlazarlo con este ID de Holded
                        // Si ya tiene un contact primary distinto, quizás se deba meter en secondary_contacts.
                        // Asumimos para este fix que si está vacío, es el primary.
                        if (empty($matchedClient->contact)) {
                            $matchedClient->update(['contact' => $contactId]);
                        } else {
                           // Añadir a secundarios si ya tiene uno distinto
                           $secondaries = $matchedClient->secondary_contacts ?? [];
                           if (!in_array($contactId, $secondaries)) {
                               $secondaries[] = $contactId;
                               $matchedClient->update(['secondary_contacts' => $secondaries]);
                           }
                        }
                        $matchedCount++;
                    } else {
                        $this->line('');
                        $this->warn("No se encontró cliente local para el CIF: {$cif} (Holded: {$name} - ID: {$contactId})");
                        $unmatchedCount++;
                    }
                } else {
                    $this->line('');
                    $this->warn("El contacto en Holded '{$name}' (ID: {$contactId}) no tiene CIF configurado.");
                    $unmatchedCount++;
                }
            } else {
                 $this->line('');
                 $this->error("El contact ID {$contactId} existe en facturas locales pero no se encontró en la API de Holded.");
                 $unmatchedCount++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->info("Proceso completado. Enlazados: {$matchedCount}. No enlazados: {$unmatchedCount}.");
        
        return 0;
    }
}
