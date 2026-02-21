---
description: Cómo configurar las copias de seguridad de Laravel en Google Drive en un servidor nuevo
---
# Configuración de Backups en Google Drive para Laravel

Sigue estos pasos para migrar o configurar el sistema de copias de seguridad en una máquina nueva.

## 1. Prerrequisitos
Asegúrate de tener instalados los siguientes paquetes en tu proyecto Laravel:
- `spatie/laravel-backup`
- `masbug/flysystem-google-drive-ext`
- `google/apiclient`

## 2. Configuración del Entorno
Añade las siguientes variables a tu archivo `.env`:

```env
# Credenciales de la API de Google Drive
GOOGLE_DRIVE_CLIENT_ID=tu_client_id
GOOGLE_DRIVE_CLIENT_SECRET=tu_client_secret
GOOGLE_DRIVE_REFRESH_TOKEN=tu_refresh_token
GOOGLE_DRIVE_FOLDER_ID=tu_target_folder_id

# Email de Notificación del Administrador
ADMIN_EMAIL=admin@example.com
```

## 3. Registrar el Driver de Almacenamiento
Asegúrate de que `AppServiceProvider.php` (o un proveedor dedicado) contenga la lógica `Storage::extend` para el driver `google`:

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
En `config/filesystems.php`, añade el disco `google`:

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
- Establece `destination.disks` a `['google']`.
- Establece `name` a `''` si quieres los archivos directamente en la raíz de la carpeta.
- Configura la retención en `cleanup.default_strategy`.

## 6. Programar las Tareas
En `routes/console.php`:

```php
Schedule::command('backup:run')->dailyAt('01:00')->then(fn () => Artisan::call('backup:clean'));
```

## 7. Verificar
Ejecuta manualmente para probar:
```bash
php artisan backup:run --only-to-disk=google
php artisan backup:list
```
