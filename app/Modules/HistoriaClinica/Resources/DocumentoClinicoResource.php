<?php

namespace App\Modules\HistoriaClinica\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentoClinicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'historia_clinica_id' => $this->historia_clinica_id,
            'tipo' => $this->tipo?->value ?? $this->getRawOriginal('tipo'),
            'tipo_label' => $this->tipo_label ?? $this->tipo,
            'nombre_archivo' => $this->nombre_archivo,
            'fecha_documento' => $this->fecha_documento?->format('Y-m-d'),
            'fecha_documento_formatted' => $this->fecha_documento?->format('d/m/Y'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
