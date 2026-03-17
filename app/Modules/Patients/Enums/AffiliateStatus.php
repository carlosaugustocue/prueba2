<?php

namespace App\Modules\Patients\Enums;

enum AffiliateStatus: string
{
    case ACTIVO = 'ACTIVO';
    case INACTIVO = 'INACTIVO';

    public function label(): string
    {
        return __('social_security.affiliate_status.' . $this->value);
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
