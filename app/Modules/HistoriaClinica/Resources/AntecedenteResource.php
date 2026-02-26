<?php

namespace App\Modules\HistoriaClinica\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AntecedenteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'historia_clinica_id' => $this->historia_clinica_id,
            'tipo' => $this->tipo?->value ?? $this->getRawOriginal('tipo'),
            'tipo_label' => $this->tipo_label ?? $this->tipo,
            'descripcion' => $this->descripcion,
            'fecha_registro' => $this->fecha_registro?->format('Y-m-d'),
            'fecha_registro_formatted' => $this->fecha_registro?->format('d/m/Y'),
            'profesional_id' => $this->profesional_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
