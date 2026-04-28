<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleDriveDocumentService
{
    /**
     * @param string $disk Nombre del disco (ej. 'google_facturas' o 'google_presupuestos')
     * @param string $rootId ID de la carpeta raíz
     * @param array $path Carpetas anidadas a crear/buscar desde el root. Ej: ['2023', 'VENTAS', '1tri']
     * @param string $fileName Nombre del archivo a crear/actualizar
     * @param string $fileContent Contenido binario del PDF
     * @param string|null $existingFileId ID del archivo si ya existe
     * @return string|null El ID del archivo creado/actualizado, o null si falló
     */
    public function uploadDocument(
        string $disk,
        string $rootId,
        array $path,
        string $fileName,
        string $fileContent,
        ?string $existingFileId = null
    ): ?string {
        try {
            $adapter = Storage::disk($disk)->getAdapter();
            $service = $adapter->getService();

            $currentParentId = $rootId;

            // Navegar o crear las carpetas del path
            foreach ($path as $folderName) {
                $currentParentId = $this->findOrCreateFolder($service, $folderName, $currentParentId);
            }

            $folderId = $currentParentId;

            // Si ya existe un archivo con ID, intentar actualizarlo
            if ($existingFileId) {
                try {
                    $file = $service->files->get($existingFileId, ['fields' => 'parents, trashed']);
                    if (!$file->getTrashed()) {
                        $previousParents = implode(',', $file->getParents());
                        $service->files->update($existingFileId, new DriveFile(['name' => $fileName]), [
                            'data' => $fileContent,
                            'mimeType' => 'application/pdf',
                            'uploadType' => 'multipart',
                            'addParents' => $folderId,
                            'removeParents' => $previousParents,
                            'fields' => 'id'
                        ]);
                        return $existingFileId;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Drive file not found by ID, continuing: ' . $e->getMessage());
                }
            }

            // Si no existe ID, o si el existente falló/está en papelera, buscar por nombre
            $fileOptParams = [
                'q' => "'$folderId' in parents and name = '$fileName' and trashed = false",
                'fields' => 'files(id)'
            ];
            
            $existingFiles = $service->files->listFiles($fileOptParams)->getFiles();

            if (count($existingFiles) > 0) {
                $fileId = $existingFiles[0]->getId();
                $service->files->update($fileId, new DriveFile(), [
                    'data' => $fileContent,
                    'mimeType' => 'application/pdf',
                    'uploadType' => 'multipart',
                    'fields' => 'id'
                ]);
                return $fileId;
            }

            // Si definitivamente no existe, crear uno nuevo
            $fileId = $service->files->create(new DriveFile([
                'name' => $fileName,
                'parents' => [$folderId]
            ]), [
                'data' => $fileContent,
                'mimeType' => 'application/pdf',
                'uploadType' => 'multipart',
                'fields' => 'id'
            ])->getId();

            return $fileId;

        } catch (Exception $e) {
            Log::error('Fallo al subir documento a Google Drive (' . $disk . '): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param string $disk Nombre del disco
     * @param string $fileId ID del archivo
     * @return string|null El contenido binario del archivo, o null si falla
     */
    public function getDocumentContent(string $disk, string $fileId): ?string
    {
        try {
            $adapter = Storage::disk($disk)->getAdapter();
            $service = $adapter->getService();
            $response = $service->files->get($fileId, ['alt' => 'media']);
            
            return $response->getBody()->getContents();
        } catch (\Throwable $e) {
            Log::warning('No se pudo descargar el documento de Drive (' . $disk . ', ID: ' . $fileId . '): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Elimina un documento de Google Drive.
     * 
     * @param string $disk Nombre del disco
     * @param string $fileId ID del archivo a eliminar
     * @return bool True si se eliminó con éxito, False en caso contrario
     */
    public function deleteDocument(string $disk, string $fileId): bool
    {
        try {
            $adapter = Storage::disk($disk)->getAdapter();
            $service = $adapter->getService();
            $service->files->delete($fileId);
            return true;
        } catch (\Exception $e) {
            Log::warning('No se pudo borrar el archivo de Drive (' . $disk . ', ID: ' . $fileId . '): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mueve un documento existente en Google Drive a una nueva ruta de carpetas.
     * 
     * @param string $disk Nombre del disco
     * @param string $fileId ID del archivo a mover
     * @param string $rootId ID de la carpeta raíz
     * @param array $newPath Nueva ruta de carpetas anidadas
     * @return bool True si se movió con éxito, False en caso contrario
     */
    public function moveDocument(string $disk, string $fileId, string $rootId, array $newPath): bool
    {
        try {
            $adapter = Storage::disk($disk)->getAdapter();
            $service = $adapter->getService();

            $currentParentId = $rootId;
            foreach ($newPath as $folderName) {
                $currentParentId = $this->findOrCreateFolder($service, $folderName, $currentParentId);
            }
            $targetFolderId = $currentParentId;

            // Obtener carpetas padre actuales
            $file = $service->files->get($fileId, ['fields' => 'parents']);
            $previousParents = implode(',', $file->getParents());

            // Si ya está en la carpeta de destino, no hacer nada
            if (str_contains($previousParents, $targetFolderId)) {
                return true;
            }

            // Mover archivo
            $service->files->update($fileId, new DriveFile(), [
                'addParents' => $targetFolderId,
                'removeParents' => $previousParents,
                'fields' => 'id, parents'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Fallo al mover documento en Google Drive (' . $disk . ', ID: ' . $fileId . '): ' . $e->getMessage());
            return false;
        }
    }

    private function findOrCreateFolder($service, $name, $parentId)
    {
        $optParams = [
            'q' => "'$parentId' in parents and mimeType = 'application/vnd.google-apps.folder' and name = '$name' and trashed = false",
            'fields' => 'files(id)'
        ];
        $files = $service->files->listFiles($optParams)->getFiles();
        
        if (count($files) > 0) {
            return $files[0]->getId();
        }

        $folder = $service->files->create(new DriveFile([
            'name' => $name, 
            'mimeType' => 'application/vnd.google-apps.folder', 
            'parents' => [$parentId]
        ]), ['fields' => 'id']);
        
        return $folder->getId();
    }
}
