<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ignorar los E_USER_NOTICE del paquete de minishlink/web-push sobre GMP y BCMath
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            if (strpos($message, 'It is highly recommended to install the GMP or BCMath extension') !== false) {
                return true; // Ignorar error
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, E_USER_NOTICE | E_USER_WARNING);

        try {
            \Illuminate\Support\Facades\Storage::extend('google', function ($app, $config) {
                if (empty($config['clientId']) || empty($config['clientSecret']) || empty($config['refreshToken'])) {
                    throw new \Exception("Missing Google Drive configuration");
                }

                $client = new \Google\Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                
                $accessToken = $client->fetchAccessTokenWithRefreshToken($config['refreshToken']);
                if (isset($accessToken['error'])) {
                    throw new \Exception("Google Drive Auth Error: " . ($accessToken['error_description'] ?? $accessToken['error']));
                }
                
                $client->setAccessToken($accessToken);

                $service = new \Google\Service\Drive($client);
                $options = !empty($config['teamDriveId']) ? ['teamDriveId' => $config['teamDriveId']] : [];
                $options['use_path_style'] = false; // Legacy compatibility
                $options['useDisplayPaths'] = false; // Force ID-based resolution (v2+)
                
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folderId'] ?? 'root', $options);
                $driver = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Google Drive Backup Extension Error: " . $e->getMessage());
        }
    }
}
