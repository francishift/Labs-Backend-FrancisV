---
description: How to setup Laravel Google Drive Backups on a new server
---
# Setup Laravel Google Drive Backups

Follow these steps to migrate or setup the backup system on a new machine.

## 1. Prerequisites
Ensure you have the following packages installed in your Laravel project:
- `spatie/laravel-backup`
- `masbug/flysystem-google-drive-ext`
- `google/apiclient`

## 2. Environment Configuration
Add the following variables to your `.env` file:

```env
# Google Drive API Credentials
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
GOOGLE_DRIVE_REFRESH_TOKEN=your_refresh_token
GOOGLE_DRIVE_FOLDER_ID=your_target_folder_id

# Admin Notification Email
ADMIN_EMAIL=admin@example.com
```

## 3. Register the Storage Driver
Ensure `AppServiceProvider.php` (or a dedicated provider) contains the `Storage::extend` logic for the `google` driver:

```php
Storage::extend('google', function ($app, $config) {
    $client = new \Google\Client();
    $client->setClientId($config['clientId']);
    $client->setClientSecret($config['clientSecret']);
    $client->setAccessToken($client->fetchAccessTokenWithRefreshToken($config['refreshToken']));
    
    $service = new \Google\Service\Drive($client);
    $options = ['useDisplayPaths' => false]; // Important for ID-based folder scoping
    
    $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folderId'] ?? 'root', $options);
    $driver = new \League\Flysystem\Filesystem($adapter);

    return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
});
```

## 4. Register the Disk
In `config/filesystems.php`, add the `google` disk:

```php
'google' => [
    'driver' => 'google',
    'clientId' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'clientSecret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'refreshToken' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    'folderId' => env('GOOGLE_DRIVE_FOLDER_ID'),
],
```

## 5. Configure Backup Behavior
In `config/backup.php`:
- Set `destination.disks` to `['google']`.
- Set `name` to `''` if you want files directly in the root of the folder.
- Configure retention in `cleanup.default_strategy`.

## 6. Schedule the Tasks
In `routes/console.php`:

```php
Schedule::command('backup:run')->dailyAt('01:00')->then(fn () => Artisan::call('backup:clean'));
```

## 7. Verify
Run manually to test:
```bash
php artisan backup:run --only-to-disk=google
php artisan backup:list
```
