<?php

namespace App\Modules\HistoriaClinica\Models;

use App\Modules\HistoriaClinica\Enums\TipoAntecedente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Antecedente extends Model
{
    protected $table = 'antecedentes';

    protected $fillable = [
        'historia_clinica_id',
        'tipo',
        'descripcion',
        'fecha_registro',
        'profesional_id',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
        'tipo' => TipoAntecedente::class,
    ];

    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class, 'historia_clinica_id');
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'profesional_id');
    }

    public function getTipoLabelAttribute(): string
    {
        return $this->tipo?->label() ?? $this->tipo;
    }
}
