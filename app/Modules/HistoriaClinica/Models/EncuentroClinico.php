<?php

namespace App\Modules\HistoriaClinica\Models;

use App\Modules\Core\Traits\HasUuid;
use App\Modules\HistoriaClinica\Enums\TipoAtencion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EncuentroClinico extends Model
{
    use HasUuid;

    protected $table = 'encuentros_clinicos';

    protected $fillable = [
        'uuid',
        'historia_clinica_id',
        'tipo_atencion',
        'fecha_atencion',
        'profesional_id',
        'especialidad_id',
        'motivo_consulta',
        'enfermedad_actual',
        'estado_mental',
        'firma_digital',
    ];

    protected $casts = [
        'fecha_atencion' => 'date',
        'tipo_atencion' => TipoAtencion::class,
    ];

    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class, 'historia_clinica_id');
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'profesional_id');
    }

    public function examenFisico(): HasOne
    {
        return $this->hasOne(ExamenFisico::class, 'encuentro_id');
    }

    public function getTipoAtencionLabelAttribute(): string
    {
        return $this->tipo_atencion?->label() ?? $this->tipo_atencion;
    }
}
