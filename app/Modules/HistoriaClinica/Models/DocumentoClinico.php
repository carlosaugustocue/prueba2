<?php

namespace App\Modules\HistoriaClinica\Models;

use App\Modules\HistoriaClinica\Enums\TipoDocumentoClinico;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoClinico extends Model
{
    protected $table = 'documentos_clinicos';

    protected $fillable = [
        'historia_clinica_id',
        'tipo',
        'nombre_archivo',
        'ruta_almacenamiento',
        'hash_integridad',
        'fecha_documento',
        'uploaded_by',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'tipo' => TipoDocumentoClinico::class,
    ];

    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class, 'historia_clinica_id');
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'uploaded_by');
    }

    public function getTipoLabelAttribute(): string
    {
        return $this->tipo?->label() ?? $this->tipo;
    }
}
