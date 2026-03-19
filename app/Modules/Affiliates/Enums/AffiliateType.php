<?php

namespace App\Modules\Affiliates\Enums;

/**
 * Tipo de afiliado (cotizante o beneficiario).
 * La columna en BD sigue siendo `patient_type` por legado de migraciones.
 */
enum AffiliateType: string
{
    case COTIZANTE = 'cotizante';
    case BENEFICIARIO = 'beneficiario';

    public function label(): string
    {
        return __('social_security.patient_type.'.$this->value);
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
