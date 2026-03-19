<?php

namespace App\Modules\Affiliates\Enums;

/**
 * Tipo de afiliado (cotizante o beneficiario).
 * Nota: el nombre del enum y la columna en BD siguen siendo "PatientType" / "patient_type"
 * por legado; en la UI y documentación se usa "afiliado" y "tipo de afiliado".
 */
enum PatientType: string
{
    case COTIZANTE = 'cotizante';
    case BENEFICIARIO = 'beneficiario';

    public function label(): string
    {
        return __('social_security.patient_type.' . $this->value);
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
