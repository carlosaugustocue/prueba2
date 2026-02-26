<?php

namespace App\Modules\HistoriaClinica\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EncuentroClinicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'historia_clinica_id' => $this->historia_clinica_id,
            'tipo_atencion' => $this->tipo_atencion?->value ?? $this->getRawOriginal('tipo_atencion'),
            'tipo_atencion_label' => $this->tipo_atencion_label ?? $this->tipo_atencion,
            'fecha_atencion' => $this->fecha_atencion?->format('Y-m-d'),
            'fecha_atencion_formatted' => $this->fecha_atencion?->format('d/m/Y'),
            'motivo_consulta' => $this->motivo_consulta,
            'enfermedad_actual' => $this->enfermedad_actual,
            'estado_mental' => $this->estado_mental,
            'profesional_id' => $this->profesional_id,
            'created_at' => $this->created_at?->toIso8601String(),

            'historia_clinica' => $this->whenLoaded('historiaClinica', fn () => new HistoriaClinicaResource($this->historiaClinica)),
            'examen_fisico' => $this->whenLoaded('examenFisico', fn () => new ExamenFisicoResource($this->examenFisico)),
        ];
    }
}
