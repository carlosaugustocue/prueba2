<?php

namespace App\Modules\HistoriaClinica\Models;

use App\Modules\HistoriaClinica\Enums\AuditoriaHcAccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaHc extends Model
{
    protected $table = 'auditoria_hc';

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'tabla_afectada',
        'registro_id',
        'accion',
        'usuario_id',
        'ip_origen',
        'datos_anteriores',
        'datos_nuevos',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'accion' => AuditoriaHcAccion::class,
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'usuario_id');
    }
}
