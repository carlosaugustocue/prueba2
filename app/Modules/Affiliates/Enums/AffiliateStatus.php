<?php

namespace App\Modules\Affiliates\Enums;

enum AffiliateStatus: string
{
    case ACTIVO = 'ACTIVO';
    case INACTIVO = 'INACTIVO';
    case SUSPENDIDO = 'SUSPENDIDO';

    public function label(): string
    {
        return __('social_security.affiliate_status.' . $this->value);
    }

    /** True only for ACTIVO; INACTIVO and SUSPENDIDO are considered not active. */
    public function isActive(): bool
    {
        return $this === self::ACTIVO;
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
