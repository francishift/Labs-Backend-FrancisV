---
description: Cómo configurar las copias de seguridad de Laravel en Google Drive en un servidor nuevo
---
# Configuración de Backups en Google Drive para Laravel

El siguiente procedimiento detalla cómo migrar o configurar el sistema de copias de seguridad en un nuevo despliegue.

## 1. Prerrequisitos
Es requerido tener instalados los siguientes paquetes en el proyecto Laravel:
- `spatie/laravel-backup`
- `masbug/flysystem-google-drive-ext`
- `google/apiclient`

## 2. Configuración del Entorno
Se deben añadir las siguientes variables al archivo `.env`:

```env
# Credenciales de la API de Google Drive
GOOGLE_DRIVE_CLIENT_ID=client_id_asignado
GOOGLE_DRIVE_CLIENT_SECRET=client_secret_asignado
GOOGLE_DRIVE_REFRESH_TOKEN=refresh_token_asignado
GOOGLE_DRIVE_FOLDER_ID=target_folder_id_asignado

# Email de Notificación del Administrador
ADMIN_EMAIL=admin@example.com
```

## 3. Registrar el Driver de Almacenamiento
Se debe confirmar que `AppServiceProvider.php` (o un proveedor dedicado) contenga la lógica `Storage::extend` para el driver `google`:

```php
Storage::extend('google', function ($app, $config) {
    $client = new \Google\Client();
    $client->setClientId($config['clientId']);
    $client->setClientSecret($config['clientSecret']);
    $client->setAccessToken($client->fetchAccessTokenWithRefreshToken($config['refreshToken']));
    
    $service = new \Google\Service\Drive($client);
    $options = ['useDisplayPaths' => false]; // Importante para el alcance de carpetas basado en ID
    
    $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folderId'] ?? 'root', $options);
    $driver = new \League\Flysystem\Filesystem($adapter);

    return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
});
```

## 4. Registrar el Disco
En `config/filesystems.php`, se añade el disco `google`:

```php
'google' => [
    'driver' => 'google',
    'clientId' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'clientSecret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'refreshToken' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    'folderId' => env('GOOGLE_DRIVE_FOLDER_ID'),
],
```

## 5. Configurar el Comportamiento del Backup
En `config/backup.php`:
- Establecer `destination.disks` a `['google']`.
- Establecer `name` a `''` si se desean ubicar los archivos directamente en la raíz de la carpeta.
- Configurar la retención en `cleanup.default_strategy`.

## 6. Programar las Tareas
En `routes/console.php`:

```php
Schedule::command('backup:run')->dailyAt('01:00')->then(fn () => Artisan::call('backup:clean'));
```

## 7. Verificar
Ejecución manual recomendada para comprobación:
```bash
php artisan backup:run --only-to-disk=google
php artisan backup:list
```
