<?php

namespace App\Modules\HistoriaClinica\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamenFisico extends Model
{
    protected $table = 'examenes_fisicos';

    protected $fillable = [
        'encuentro_id',
        'peso_kg',
        'talla_cm',
        'imc',
        'presion_arterial_sistolica',
        'presion_arterial_diastolica',
        'frecuencia_cardiaca',
        'frecuencia_respiratoria',
        'temperatura',
        'saturacion_oxigeno',
        'hallazgos_por_sistema',
        'resumen_general',
    ];

    protected $casts = [
        'peso_kg' => 'decimal:2',
        'talla_cm' => 'decimal:2',
        'imc' => 'decimal:2',
        'temperatura' => 'decimal:2',
        'hallazgos_por_sistema' => 'array',
    ];

    public function encuentro(): BelongsTo
    {
        return $this->belongsTo(EncuentroClinico::class, 'encuentro_id');
    }

    /**
     * Calcula IMC a partir de peso (kg) y talla (cm). Guardar en imc o calcular al vuelo.
     */
    public static function calcularImc(?float $pesoKg, ?float $tallaCm): ?float
    {
        if ($pesoKg === null || $tallaCm === null || $tallaCm <= 0) {
            return null;
        }
        $tallaM = $tallaCm / 100;
        return round($pesoKg / ($tallaM * $tallaM), 2);
    }
}
