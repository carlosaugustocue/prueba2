<?php

namespace App\Foundation;

use Illuminate\Foundation\Vite as BaseVite;

/**
 * En desarrollo, usa el host de la petición para la URL de Vite (hot file).
 * Así la app carga el front desde otra máquina: quien abre la página recibe
 * assets desde el mismo host (ej. http://192.168.1.50:5173) en lugar de localhost.
 */
class Vite extends BaseVite
{
    /**
     * Get the path to a given asset when running in HMR mode.
     * Uses the request host so the front loads when opening the app from another machine.
     */
    protected function hotAsset($asset): string
    {
        $baseUrl = rtrim(file_get_contents($this->hotFile()), '/');

        if (app()->runningInConsole() || ! request()->hasHeader('Host')) {
            return $baseUrl.'/'.$asset;
        }

        $requestHost = request()->getHost();
        $parsed = parse_url($baseUrl);

        if ($parsed === false || ! isset($parsed['scheme'])) {
            return $baseUrl.'/'.$asset;
        }

        $port = $parsed['port'] ?? 5173;
        $baseUrl = ($parsed['scheme'] ?? 'http').'://'.$requestHost.':'.$port;

        return $baseUrl.'/'.$asset;
    }
}
