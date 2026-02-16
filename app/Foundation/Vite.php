<?php

namespace App\Foundation;

use Illuminate\Foundation\Vite as BaseVite;

/**
 * En desarrollo local:
 * - Si la petición viene de otro equipo (ej. celular por IP), usamos siempre el build
 *   para que todos los assets se sirvan desde el mismo :8000 (no se depende del puerto 5173).
 * - Si la petición es desde el mismo equipo (localhost), usamos hot cuando exista y
 *   la URL de hot con el host de la petición para que funcione por IP en la misma máquina.
 */
class Vite extends BaseVite
{
    /**
     * Cuando la petición viene de otro equipo (IP de red), no usar hot:
     * así el front carga todo desde Laravel (:8000) y no desde Vite (:5173).
     */
    public function hotFile(): string
    {
        if (! app()->runningInConsole() && request()->hasHeader('Host')) {
            $host = request()->getHost();
            if ($host !== 'localhost' && $host !== '127.0.0.1') {
                return public_path('hot').'.no-remote';
            }
        }

        return parent::hotFile();
    }

    /**
     * Get the path to a given asset when running in HMR mode.
     * Uses the request host so el mismo PC puede abrir por IP y cargar Vite.
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
