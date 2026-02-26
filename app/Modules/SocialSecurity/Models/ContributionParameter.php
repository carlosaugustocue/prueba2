<?php

namespace App\Modules\SocialSecurity\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Parámetros de aportes y valores de sistema con vigencia temporal.
 * Toda la configuración normativa (porcentajes, SMLMV, topes) vive aquí; no usar constantes en código.
 */
class ContributionParameter extends Model
{
    protected $table = 'contribution_parameters';

    protected $fillable = [
        'type',
        'subtype',
        'value',
        'value_type',
        'valid_from',
        'valid_to',
        'description',
        'legal_reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'metadata' => 'array',
        ];
    }

    /**
     * Scope: parámetros vigentes para una fecha dada.
     */
    public function scopeValidForDate($query, $date): mixed
    {
        $date = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : \Carbon\Carbon::parse($date)->format('Y-m-d');

        return $query
            ->where('valid_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
            });
    }

    /**
     * Obtiene el valor numérico de un parámetro vigente para una fecha.
     * Único punto de lectura de valores; evita magic numbers en el resto del código.
     */
    public static function getValueForDate(string $type, string $subtype, $date): ?float
    {
        $param = static::validForDate($date)
            ->where('type', $type)
            ->where('subtype', $subtype)
            ->first();

        return $param !== null ? (float) $param->value : null;
    }

    /**
     * Obtiene todos los parámetros vigentes para una fecha, agrupados por type y subtype.
     * Estructura: [ 'HEALTH' => [ 'TOTAL' => 12.5, 'EMPLOYER' => 8.5, ... ], 'SYSTEM' => [ 'SMLMV' => 1750905, ... ], ... ]
     */
    public static function getAllValidForDate($date): array
    {
        $date = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : \Carbon\Carbon::parse($date)->format('Y-m-d');

        return static::validForDate($date)
            ->get()
            ->groupBy('type')
            ->map(fn ($items) => $items->mapWithKeys(fn ($p) => [$p->subtype => (float) $p->value])->all())
            ->all();
    }

    /**
     * Indica si el parámetro es de tipo porcentaje (para aplicar sobre IBC u otra base).
     */
    public function isPercentage(): bool
    {
        return $this->value_type === 'PERCENTAGE';
    }

    /**
     * Indica si el parámetro es un monto absoluto (ej. SMLMV en pesos).
     */
    public function isAmount(): bool
    {
        return $this->value_type === 'AMOUNT';
    }

    /**
     * Indica si el parámetro es un multiplicador (ej. número de SMLMV para IBC máximo).
     */
    public function isMultiplier(): bool
    {
        return $this->value_type === 'MULTIPLIER';
    }
}
