<?php

namespace App\Modules\HistoriaClinica\Models;

use App\Modules\Core\Traits\HasUuid;
use App\Modules\HistoriaClinica\Enums\HistoriaClinicaEstado;
use App\Modules\Patients\Models\Affiliate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HistoriaClinica extends Model
{
    use HasUuid;

    protected $table = 'historia_clinica';

    protected $fillable = [
        'uuid',
        'numero_historia',
        'affiliate_id',
        'fecha_apertura',
        'estado',
        'created_by',
        'firma_digital',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'estado' => HistoriaClinicaEstado::class,
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'created_by');
    }

    public function encuentros(): HasMany
    {
        return $this->hasMany(EncuentroClinico::class, 'historia_clinica_id')->orderByDesc('fecha_atencion');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoClinico::class, 'historia_clinica_id');
    }

    public function antecedentes(): HasMany
    {
        return $this->hasMany(Antecedente::class, 'historia_clinica_id')->orderBy('fecha_registro');
    }

    public function getEstadoLabelAttribute(): string
    {
        return $this->estado?->label() ?? $this->estado;
    }
}
