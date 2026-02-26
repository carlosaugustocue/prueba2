<?php

namespace App\Modules\HistoriaClinica\Services;

use App\Modules\HistoriaClinica\Enums\AuditoriaHcAccion;
use App\Modules\HistoriaClinica\Models\AuditoriaHc;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditoriaHcService
{
    public static function log(
        string $tablaAfectada,
        string $registroId,
        AuditoriaHcAccion $accion,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null
    ): void {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        AuditoriaHc::create([
            'tabla_afectada' => $tablaAfectada,
            'registro_id' => $registroId,
            'accion' => $accion,
            'usuario_id' => $user->id,
            'ip_origen' => Request::ip(),
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
        ]);
    }

    public static function logRead(string $tablaAfectada, string $registroId): void
    {
        self::log($tablaAfectada, $registroId, AuditoriaHcAccion::READ);
    }

    public static function logCreate(string $tablaAfectada, string $registroId, array $datosNuevos): void
    {
        self::log($tablaAfectada, $registroId, AuditoriaHcAccion::CREATE, null, $datosNuevos);
    }

    public static function logUpdate(string $tablaAfectada, string $registroId, array $datosAnteriores, array $datosNuevos): void
    {
        self::log($tablaAfectada, $registroId, AuditoriaHcAccion::UPDATE, $datosAnteriores, $datosNuevos);
    }
}
