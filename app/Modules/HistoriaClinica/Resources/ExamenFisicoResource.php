<?php

namespace App\Modules\HistoriaClinica\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamenFisicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'encuentro_id' => $this->encuentro_id,
            'peso_kg' => $this->peso_kg !== null ? (float) $this->peso_kg : null,
            'talla_cm' => $this->talla_cm !== null ? (float) $this->talla_cm : null,
            'imc' => $this->imc !== null ? (float) $this->imc : null,
            'presion_arterial_sistolica' => $this->presion_arterial_sistolica,
            'presion_arterial_diastolica' => $this->presion_arterial_diastolica,
            'frecuencia_cardiaca' => $this->frecuencia_cardiaca,
            'frecuencia_respiratoria' => $this->frecuencia_respiratoria,
            'temperatura' => $this->temperatura !== null ? (float) $this->temperatura : null,
            'saturacion_oxigeno' => $this->saturacion_oxigeno,
            'hallazgos_por_sistema' => $this->hallazgos_por_sistema,
            'resumen_general' => $this->resumen_general,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
