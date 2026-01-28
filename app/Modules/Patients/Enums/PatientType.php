<?php

namespace App\Modules\Patients\Enums;

enum PatientType: string
{
    case COTIZANTE = 'cotizante';
    case BENEFICIARIO = 'beneficiario';

    public function label(): string
    {
        return match($this) {
            self::COTIZANTE => 'Cotizante',
            self::BENEFICIARIO => 'Beneficiario',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
