<?php

namespace App\Modules\HistoriaClinica\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoriaClinicaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'numero_historia' => $this->numero_historia,
            'affiliate_id' => $this->affiliate_id,
            'fecha_apertura' => $this->fecha_apertura?->format('Y-m-d'),
            'fecha_apertura_formatted' => $this->fecha_apertura?->format('d/m/Y'),
            'estado' => $this->estado?->value ?? $this->getRawOriginal('estado'),
            'estado_label' => $this->estado_label ?? $this->estado,
            'created_at' => $this->created_at?->toIso8601String(),

            'affiliate' => $this->whenLoaded('affiliate', fn () => new \App\Modules\Patients\Resources\AffiliateResource($this->affiliate)),
            'encuentros' => $this->whenLoaded('encuentros', fn () => EncuentroClinicoResource::collection($this->encuentros)),
            'documentos' => $this->whenLoaded('documentos', fn () => DocumentoClinicoResource::collection($this->documentos)),
        ];
    }
}
